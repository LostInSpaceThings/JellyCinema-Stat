# 🎬 JellyCinema-Stat

A high-performance, dashboard for **Jellyfin** users. This system pulls your watch history via the Jellyfin API and calculates levels, statistics, and unlocks "Cinema Milestones" (Trophies) based on your viewing habits.
Incredibly lightweight. It connects to your API, we only have two files. 

## 🚀 Features
* **Dynamic Leveling**: RPG-style leveling system based on total titles watched. Have fun and level up like its your favorite game.
* **Trophies**: Trophies including watching certain catagories, watch time, watching certain amounts of episodes and TV, and more.
* **Runtime Analytics**: Total hours spent watching your favorite content.
*  **Archive Dashboard**: View an endless scrolling dashboard with all your media you watched.

---
<img width="1257" height="856" alt="Screenshot 2026-02-22 121631" src="https://github.com/user-attachments/assets/4fbeb851-f3f3-44ba-9856-0ac10d2033a5" />
<img width="1237" height="546" alt="Screenshot 2026-02-22 121637" src="https://github.com/user-attachments/assets/ae6b1753-958e-4c30-a240-463e0fd22ca2" />


## 🛠️ Setup & Installation

### 1. Prerequisites
* A running **Jellyfin** Server.
* A web server with **PHP 7.4+** and **cURL** enabled.

### 2. Configuration

* Each file needs to be edited to have your API KEY, URL, and USER ID. You can make API keys in the Admin Dashboard in Jellyfin.
* To get your USER ID, login to Jellyfin. Head to your user profile page. Check the URL in the browser, you will see it towards the end.
