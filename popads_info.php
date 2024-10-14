<?php
function getPopAdsCampaignInfo($apiKey, $campaignId)
{
    $url = "https://www.popads.net/api/campaign_list?key=" . $apiKey;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $campaigns = json_decode($response, true)['campaigns'];

    foreach ($campaigns as $campaign) {
        if ($campaign['id'] == $campaignId) {
            return [
                'status' => $campaign['status'],
                'budget' => number_format($campaign['budget'], 2, '.', ''),
            ];
        }
    }

    return null;
}
