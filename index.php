<?php
// --- CONFIGURATION ---
include('config.php');

function getJellyData($endpoint, $url, $key) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . $endpoint . (strpos($endpoint, '?') ? '&' : '?') . "api_key=" . $key);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $data = json_decode($res, true);
    curl_close($ch);
    return $data;
}

$items = getJellyData("/Users/$user_id/Items?Recursive=true&IsPlayed=true&IncludeItemTypes=Movie,Episode&Fields=DatePlayed,RunTimeTicks,Genres,SeriesName&SortBy=DatePlayed&SortOrder=Descending", $jellyfin_url, $api_key);
$watched_list = $items['Items'] ?? [];

$total_watched = count($watched_list);
$movie_count = 0; $show_count = 0; 
$late_night_count = 0; $mid_day_count = 0; $early_morning_count = 0;
$marathon_minutes = 0;
$genres_tracked = ['Science Fiction' => 0, 'Adventure' => 0, 'Animation' => 0, 'Horror' => 0, 'Comedy' => 0, 'Action' => 0];
$animated_movies = 0;

foreach ($watched_list as $item) {
    if ($item['Type'] == 'Movie') $movie_count++;
    if ($item['Type'] == 'Episode') {
        $show_count++;
    }
	if ($item['Type'] == 'Movie') {
        $movie_count++;
        if ($isAnimated) $animated_movie_count++;
    }
    if (isset($item['Genres'])) {
        foreach($genres_tracked as $genre_name => $count) {
            if (in_array($genre_name, $item['Genres'])) $genres_tracked[$genre_name]++;
        }
    }
    if (isset($item['RunTimeTicks'])) $marathon_minutes += $item['RunTimeTicks'] / 600000000;
    
    if (isset($item['UserData']['LastPlayedDate'])) {
        $play_hour = (int)date('H', strtotime($item['UserData']['LastPlayedDate']));
        if ($play_hour >= 1 && $play_hour <= 5) $late_night_count++;
        if ($play_hour >= 6 && $play_hour <= 9) $early_morning_count++;
        if ($play_hour >= 11 && $play_hour <= 14) $mid_day_count++;
    }
}

$marathon_hours = round($marathon_minutes / 60);
$recent_five = array_slice($watched_list, 0, 5);

$level = floor(sqrt($total_watched / 2)) ?: 1;
$next_lvl_base = 2 * pow($level + 1, 2);
$titles_needed = $next_lvl_base - $total_watched;
$current_lvl_base = 2 * pow($level, 2);
$diff = $next_lvl_base - $current_lvl_base;
$percent = ($level == 1) ? ($total_watched / $next_lvl_base) * 100 : (($total_watched - $current_lvl_base) / $diff) * 100;

$milestone_list = [
    ($movie_count >= 10), ($movie_count >= 100), ($movie_count >= 200), ($movie_count >= 300), ($movie_count >= 500),
    ($marathon_hours >= 100), ($marathon_hours >= 250), ($marathon_hours >= 500),
    ($genres_tracked['Science Fiction'] >= 10), ($genres_tracked['Adventure'] >= 10),
    ($genres_tracked['Animation'] >= 15), ($genres_tracked['Animation'] >= 50),
($animated_movie_count >= 100), 
    ($late_night_count >= 20), ($late_night_count >= 50), ($mid_day_count >= 15), ($early_morning_count >= 10),
    ($south_park_count >= 320)
];
$unlocked_count = count(array_filter($milestone_list));
$total_milestones = count($milestone_list);
$milestone_percent = ($unlocked_count / $total_milestones) * 100;
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
            --milestone-blue: #00d2ff;
        }
        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background: var(--bg-gradient); background-attachment: fixed; color: var(--text); display: flex; flex-direction: column; align-items: center; min-height: 100vh; }
        .container { width: 90%; max-width: 1200px; padding: 40px 0; }
        
        .level-card { background: var(--glass); padding: 40px; border-radius: 25px; border: 1px solid rgba(255, 255, 255, 0.05); text-align: center; margin-bottom: 20px; }
        .level-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.05); }
        
        .level-label { 
            font-size: 0.9rem; 
            text-transform: uppercase; 
            letter-spacing: 4px; 
            color: var(--milestone-blue); 
            font-weight: bold; 
            text-shadow: 0 0 10px rgba(0, 210, 255, 0.3);
        }
        
        .level-number { font-size: 3.5rem; font-weight: 200; line-height: 1; }
        
        .progress-track { width: 100%; height: 14px; background: rgba(255, 255, 255, 0.05); border-radius: 10px; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.1); }
        .progress-fill { height: 100%; width: <?php echo $percent; ?>%; background: linear-gradient(90deg, var(--accent), #ff4d8d); box-shadow: 0 0 20px rgba(215, 10, 83, 0.4); }

        .next-level-text { 
            color: var(--milestone-blue); 
            font-weight: 800; 
            text-transform: uppercase; 
            letter-spacing: 1px;
            text-shadow: 0 0 10px rgba(0, 210, 255, 0.3);
        }

        .milestone-mini-area { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; background: rgba(0,0,0,0.2); padding: 10px 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); width: fit-content; }
        .milestone-bar-container { width: 150px; height: 6px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden; }
        .milestone-bar-fill { height: 100%; width: <?php echo $milestone_percent; ?>%; background: var(--milestone-blue); box-shadow: 0 0 10px var(--milestone-blue); }

        .nav-wrapper { width: 100%; text-align: center; margin-bottom: 30px; }
        .back-btn { display: inline-block; color: var(--text-dim); text-decoration: none; font-size: 0.8rem; letter-spacing: 2px; text-transform: uppercase; padding: 10px 20px; background: var(--glass); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s; }
        .back-btn:hover { color: var(--accent); border-color: var(--accent); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .stat-card { background: var(--glass); padding: 25px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.05); text-align: center; }
        .stat-val { font-size: 2.5rem; font-weight: 200; color: var(--accent); display: block; }
        .stat-lbl { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 2px; color: var(--text-dim); }

        .section-header { display: flex; justify-content: space-between; align-items: center; margin: 40px 0 20px; }
        .section-label { font-size: 0.9rem; text-transform: uppercase; letter-spacing: 4px; color: var(--accent); font-weight: bold; }
        .view-all { color: var(--text-dim); text-decoration: none; font-size: 0.75rem; border: 1px solid rgba(255,255,255,0.1); padding: 6px 12px; border-radius: 6px; }

        .recent-list { background: var(--glass); border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.05); overflow: hidden; }
        .recent-item { display: flex; justify-content: space-between; padding: 18px 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.03); align-items: center; }
        
        .media-type-tag { font-size: 0.55rem; padding: 3px 8px; border-radius: 4px; margin-right: 12px; font-weight: 800; letter-spacing: 1px; color: #fff; display: inline-block; vertical-align: middle; }
        .tag-episode { background: var(--accent); }
        .tag-movie { background: var(--movie-tag); }

        .achievement-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; }
        .badge { background: var(--glass); padding: 15px 20px; border-radius: 18px; border: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 15px; filter: grayscale(1); opacity: 0.3; transition: 0.4s; }
        .badge.unlocked { filter: grayscale(0); opacity: 1; border: 1px solid var(--accent); box-shadow: 0 0 15px rgba(215, 10, 83, 0.3); }
        .badge-icon { font-size: 2.2rem; min-width: 45px; text-align: center; }
        .badge-info { display: flex; flex-direction: column; overflow: hidden; }
        .badge-title { font-weight: bold; font-size: 0.95rem; line-height: 1.1; white-space: nowrap; }
        .badge-desc { font-size: 0.75rem; color: var(--text-dim); margin-top: 3px; line-height: 1.2; }
    </style>
</head>
<body>
<div class="container">

    <div class="level-card">
        <div class="level-header">
            <span class="level-label">User Progress</span>
            <span class="level-number">LVL <?php echo $level; ?></span>
        </div>
        <div class="progress-track"><div class="progress-fill"></div></div>
        <div style="margin-top: 15px; display: flex; justify-content: space-between; font-size: 0.8rem;">
            <span style="color: var(--text-dim);"><?php echo $total_watched; ?> Titles Logged</span>
            <span class="next-level-text">Next Level in: <?php echo $titles_needed; ?> Titles</span>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><span class="stat-val"><?php echo $movie_count; ?></span><span class="stat-lbl">Movies</span></div>
        <div class="stat-card"><span class="stat-val"><?php echo $show_count; ?></span><span class="stat-lbl">Episodes</span></div>
        <div class="stat-card"><span class="stat-val"><?php echo $marathon_hours; ?>h</span><span class="stat-lbl">Total Time</span></div>
        <div class="stat-card"><span class="stat-val"><?php echo $late_night_count; ?></span><span class="stat-lbl">Late Night</span></div>
    </div>

    <div class="section-header">
        <span class="section-label">Last 5 Screenings</span>
        <a href="all_media.php" class="view-all">Master Archive →</a>
    </div>

    <div class="recent-list">
        <?php foreach ($recent_five as $item): 
            $isEpisode = ($item['Type'] == 'Episode');
            $tagClass = $isEpisode ? 'tag-episode' : 'tag-movie';
        ?>
            <div class="recent-item">
                <div class="media-title">
                    <span class="media-type-tag <?php echo $tagClass; ?>"><?php echo strtoupper($item['Type']); ?></span>
                    <?php echo $isEpisode ? htmlspecialchars($item['SeriesName'] ?? '') . " — " . htmlspecialchars($item['Name']) : htmlspecialchars($item['Name']); ?>
                </div>
                <div class="query-time"><?php echo date('M d, Y', strtotime($item['UserData']['LastPlayedDate'])); ?><br><?php echo date('h:i A', strtotime($item['UserData']['LastPlayedDate'])); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="section-header">
        <span class="section-label">Cinema Milestones</span>
        <div class="milestone-mini-area">
            <span style="font-size: 0.7rem; font-weight: bold; color: var(--milestone-blue); text-transform: uppercase;"><?php echo $unlocked_count; ?> / <?php echo $total_milestones; ?> Unlocked</span>
            <div class="milestone-bar-container"><div class="milestone-bar-fill"></div></div>
        </div>
    </div>

    <div class="achievement-grid">
        <div class="badge <?php echo ($movie_count >= 10) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">🍿</div>
            <div class="badge-info"><span class="badge-title">Movie Buff</span><span class="badge-desc">10 movies watched.</span></div>
        </div>
        <div class="badge <?php echo ($movie_count >= 100) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">🎞️</div>
            <div class="badge-info"><span class="badge-title">Cinephile</span><span class="badge-desc">100 movies watched.</span></div>
        </div>
        <div class="badge <?php echo ($movie_count >= 200) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">🏛️</div>
            <div class="badge-info"><span class="badge-title">Grand Cinema King</span><span class="badge-desc">200 movies watched.</span></div>
        </div>
        <div class="badge <?php echo ($movie_count >= 300) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">👑</div>
            <div class="badge-info"><span class="badge-title">Cinema Sovereign</span><span class="badge-desc">300 movies watched.</span></div>
        </div>
        <div class="badge <?php echo ($movie_count >= 500) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">🌌</div>
            <div class="badge-info"><span class="badge-title">Legendary Archivist</span><span class="badge-desc">500 movies watched.</span></div>
        </div>
        
        <div class="badge <?php echo ($early_morning_count >= 10) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">🌅</div>
            <div class="badge-info"><span class="badge-title">Early Bird</span><span class="badge-desc">10 morning watches.</span></div>
        </div>
        <div class="badge <?php echo ($mid_day_count >= 15) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">🍔</div>
            <div class="badge-info"><span class="badge-title">Lunch Break</span><span class="badge-desc">15 mid-day watches.</span></div>
        </div>
        <div class="badge <?php echo ($late_night_count >= 20) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">🌙</div>
            <div class="badge-info"><span class="badge-title">Vampire</span><span class="badge-desc">20 late-night watches.</span></div>
        </div>
        <div class="badge <?php echo ($late_night_count >= 50) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">🦇</div>
            <div class="badge-info"><span class="badge-title">Nocturnal Animal</span><span class="badge-desc">50 late-night watches.</span></div>
        </div>

        <div class="badge <?php echo ($marathon_hours >= 100) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">⏳</div>
            <div class="badge-info"><span class="badge-title">Century Club</span><span class="badge-desc">100 hours watched.</span></div>
        </div>
        <div class="badge <?php echo ($marathon_hours >= 250) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">⌛</div>
            <div class="badge-info"><span class="badge-title">Silver Screen</span><span class="badge-desc">250 hours watched.</span></div>
        </div>
        <div class="badge <?php echo ($marathon_hours >= 500) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">💎</div>
            <div class="badge-info"><span class="badge-title">Diamond Life</span><span class="badge-desc">500 hours watched.</span></div>
        </div>

        <div class="badge <?php echo ($genres_tracked['Science Fiction'] >= 10) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">🚀</div>
            <div class="badge-info"><span class="badge-title">Star Voyager</span><span class="badge-desc">10 Sci-Fi titles.</span></div>
        </div>
        <div class="badge <?php echo ($genres_tracked['Adventure'] >= 10) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">🗺️</div>
            <div class="badge-info"><span class="badge-title">Explorer</span><span class="badge-desc">10 Adventure titles.</span></div>
        </div>
        <div class="badge <?php echo ($genres_tracked['Animation'] >= 15) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">🎨</div>
            <div class="badge-info"><span class="badge-title">Toon Titan</span><span class="badge-desc">15 Animated titles.</span></div>
        </div>
        <div class="badge <?php echo ($genres_tracked['Animation'] >= 50) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">🎭</div>
            <div class="badge-info"><span class="badge-title">Ink Enthusiast</span><span class="badge-desc">50 Animated titles.</span></div>
        </div>
            <div class="badge <?php echo ($animated_movie_count >= 100) ? 'unlocked' : ''; ?>">
            <div class="badge-icon">🖌️</div>
            <div class="badge-info"><span class="badge-title">Master of Clay</span><span class="badge-desc">100 Animated movies.</span></div>
        </div>
    </div>
</div>
</body>
</html>
