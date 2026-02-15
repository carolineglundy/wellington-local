<?php

namespace App;

use Page;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;

class LocalBusinessFinderPage extends Page
{
    private static $table_name = 'LocalBusinessFinderPage';
}

class LocalBusinessFinderPageController extends \PageController
{
    private static $allowed_actions = ['search'];

    public function search(HTTPRequest $request): HTTPResponse
    {
        $query = urlencode($request->getVar('q'));
        $googleKey = Environment::getEnv('GOOGLE_PLACES_API_KEY');
        $geminiKey = Environment::getEnv('GEMINI_API_KEY');

        $googleUrl = "https://maps.googleapis.com/maps/api/place/textsearch/json?query={$query}+in+Wellington+New+Zealand&key={$googleKey}";
        $googleResponse = file_get_contents($googleUrl);
        $googleData = json_decode($googleResponse, true);
        $results = $googleData['results'] ?? [];

        $names = array_column(array_slice($results, 0, 10), 'name');
        $prompt = "Identify which of these are independent NZ small businesses vs international chains. Return ONLY a JSON object like this: {\"local\": [\"Name1\"], \"chains\": [\"Name2\"]}. List: " . implode(', ', $names);
        $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $geminiKey;
        $geminiPayload = json_encode([
            "contents" => [["parts" => [["text" => $prompt]]]]
        ]);

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $geminiPayload,
            ]
        ];
        $aiResponse = file_get_contents($geminiUrl, false, stream_context_create($opts));
        $aiResult = json_decode($aiResponse, true);

        $cleanJson = $aiResult['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        $cleanJson = str_replace(['```json', '```'], '', $cleanJson);

        return $this->getResponse()
            ->addHeader('Content-Type', 'application/json')
            ->setBody(json_encode([
                'places' => $results,
                'analysis' => json_decode(trim($cleanJson), true)
            ]));
    }
}
