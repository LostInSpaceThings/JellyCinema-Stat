<?php
include('config.php');

function getJellyData($endpoint, $url, $key) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . $endpoint . (strpos($endpoint, '?') ? '&' : '?') . "api_key=" . $key);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

$items = getJellyData("/Users/$user_id/Items?Recursive=true&IsPlayed=true&IncludeItemTypes=Movie,Episode&Fields=DatePlayed,RunTimeTicks,Genres,SeriesName,OfficialRating&SortBy=DatePlayed&SortOrder=Descending", $jellyfin_url, $api_key);
$watched_list = $items['Items'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>JellyCinema Stat</title>
    <style>
        :root { 
            --bg-gradient: linear-gradient(135deg, #0f0c29, #302b63, #24243e); 
            --accent: #d70a53; 
            --movie-tag: #5d55fa;
            --glass: rgba(255, 255, 255, 0.07); 
            --text: #ffffff; 
            --text-dim: rgba(255, 255, 255, 0.6); 
        }
        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background: var(--bg-gradient); background-attachment: fixed; color: var(--text); display: flex; flex-direction: column; align-items: center; }
        .container { width: 95%; max-width: 1100px; padding: 60px 0; }
        .header { text-align: center; margin-bottom: 40px; }
        .back-link { color: var(--accent); text-decoration: none; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px; border: 1px solid rgba(215, 10, 83, 0.3); padding: 8px 15px; border-radius: 5px; transition: 0.3s; }
        .back-link:hover { background: var(--accent); color: white; }
        
        .archive-table { width: 100%; border-collapse: collapse; background: var(--glass); border-radius: 20px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); }
        .archive-table th { text-align: left; padding: 20px; background: rgba(0,0,0,0.3); color: var(--text); text-transform: uppercase; font-size: 0.7rem; letter-spacing: 2px; }
        .archive-table td { padding: 18px 20px; border-bottom: 1px solid rgba(255,255,255,0.03); vertical-align: middle; }
        
        /* IMPROVED PILLS */
        .type-pill { 
            font-size: 0.55rem; 
            padding: 3px 8px; 
            border-radius: 4px; 
            margin-right: 12px; 
            font-weight: 800; 
            letter-spacing: 1px;
            display: inline-block;
            color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .pill-episode { background: var(--accent); }
        .pill-movie { background: var(--movie-tag); }

        strong { font-size: 1rem; color: #fff; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="index.php" class="back-link">← Return to Dashboard</a>
        <h1 style="letter-spacing: 5px; text-transform: uppercase; font-weight: 200; margin-top: 25px;">Master Archive</h1>
    </div>
    <table class="archive-table">
        <thead>
            <tr>
                <th>Media Title</th>
                <th>Genre</th>
                <th>Runtime</th>
                <th>Date Logged</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($watched_list as $item): 
                $isEpisode = ($item['Type'] == 'Episode');
                $pillClass = $isEpisode ? 'pill-episode' : 'pill-movie';
            ?>
            <tr>
                <td>
                    <span class="type-pill <?php echo $pillClass; ?>"><?php echo strtoupper($item['Type']); ?></span>
                    <strong><?php echo $isEpisode ? htmlspecialchars($item['SeriesName']) . ": " . htmlspecialchars($item['Name']) : htmlspecialchars($item['Name']); ?></strong>
                </td>
                <td style="font-size: 0.8rem; color: var(--text-dim);">
                    <?php echo isset($item['Genres']) ? implode(', ', array_slice($item['Genres'], 0, 2)) : '—'; ?>
                </td>
                <td style="font-size: 0.8rem;">
                    <?php echo isset($item['RunTimeTicks']) ? round($item['RunTimeTicks'] / 600000000) . " min" : '—'; ?>
                </td>
                <td style="font-size: 0.8rem; color: var(--text-dim);">
                    <?php echo date('M d, Y', strtotime($item['UserData']['LastPlayedDate'])); ?>
                    <span style="opacity: 0.5; font-size: 0.7rem; margin-left: 5px;"><?php echo date('h:i A', strtotime($item['UserData']['LastPlayedDate'])); ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
