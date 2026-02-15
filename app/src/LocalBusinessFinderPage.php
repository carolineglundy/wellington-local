<?php

namespace App;

use Page;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\HTTPClient;

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
        $googleKey = 'GOOGLE_PLACES_API_KEY';
        $geminiKey = 'GEMINI_API_KEY';

        $client = Injector::inst()->create(HTTPClient::class);

        $googleUrl = "https://maps.googleapis.com/maps/api/place/textsearch/json?query={$query}+in+Wellington&key={$googleKey}";
        $googleResponse = $client->get($googleUrl);
        $googleData = json_decode($googleResponse->getBody(), true);
        $results = $googleData['results'] ?? [];


        $names = array_column(array_slice($results, 0, 10), 'name');
        $prompt = "Identify which of these are independent NZ small businesses vs international chains. Return ONLY a JSON object like this: {\"local\": [\"Name1\"], \"chains\": [\"Name2\"]}. List: " . implode(', ', $names);

        $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $geminiKey;
        $geminiPayload = [
            "contents" => [["parts" => [["text" => $prompt]]]]
        ];

        $aiResponse = $client->post($geminiUrl, [
            'Content-Type' => 'application/json'
        ], [
            'body' => json_encode($geminiPayload)
        ]);

        $aiResult = json_decode($aiResponse->getBody(), true);

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
