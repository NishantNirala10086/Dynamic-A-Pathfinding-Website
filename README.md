# 🌐 Dynamic-A-Pathfinding-Website 

## 🚀 Overview

This project is a **web-based implementation of the A* (A-star) pathfinding algorithm** that dynamically finds the most efficient path between two locations using the formula:
[
f(n) = g(n) + h(n)
]
Where:

* **g(n)** → Actual distance (cost) from the starting node to the current node.
* **h(n)** → Heuristic (estimated) cost from the current node to the goal node.

Users can **add cities**, **define paths (edges)**, and **calculate the optimal route** between any two locations. The system uses **PHP** for frontend and database interaction, **Python** for backend path computation, and **MySQL** for data storage (via **phpMyAdmin**).

---

## 🧠 Features

✅ Add multiple cities (with latitude, longitude, and heuristic values)
✅ Create connections (edges) between cities
✅ Use Python A* algorithm to calculate the shortest route
✅ Store all results and routes in MySQL
✅ Clean and dynamic web interface built using PHP
✅ API-based communication between PHP and Python backend

---

## ⚙️ Tech Stack

| Layer         | Technology Used            |
| ------------- | -------------------------- |
| **Frontend**  | PHP, HTML, CSS, JavaScript |
| **Backend**   | Python (`a_star_api.py`)   |
| **Database**  | MySQL (via phpMyAdmin)     |
| **Server**    | Apache (XAMPP/WAMP)        |
| **Algorithm** | A* (A-Star) Pathfinding    |

---

## 🗂️ Project Structure


Astar-WebApp/
│
├── a_star_api.py        # Python backend implementing the A* algorithm
├── add_city.php         # PHP script to add a new city to the database
├── add_edge.php         # PHP script to connect two cities (add edge)
├── index.php            # Main page (user interface)
├── save_result.php      # Saves the computed A* result to the database
├── save_route.php       # Saves the route path sequence
├── database.sql         # (optional) SQL file to create tables
└── README.md            # Project documentation




## 🖥️ How It Works

1. **Add Cities:**
   Use the `add_city.php` form to input each city’s name, latitude, longitude, and heuristic value.
2. **Connect Cities:**
   Use `add_edge.php` to link cities and assign path costs.
3. **Run A***
   When the user selects a start and end city on `index.php`, PHP sends the data to the Python backend (`a_star_api.py`).
4. **Python Processing:**
   The Python script computes the shortest path using the A* algorithm and sends the result back as JSON.
5. **Result Storage:**
   PHP receives and stores the results in the MySQL database through `save_result.php` and `save_route.php`.
6. **Display Path:**
   The optimized route and cost are displayed on the web page.

---

## 🧩 Database Structure

### Table: `cities`

| Column    | Type         | Description            |
| --------- | ------------ | ---------------------- |
| id        | INT          | Primary key            |
| name      | VARCHAR(100) | City name              |
| latitude  | FLOAT        | Latitude               |
| longitude | FLOAT        | Longitude              |
| heuristic | FLOAT        | Heuristic value (h(n)) |

### Table: `edges`

| Column    | Type  | Description         |
| --------- | ----- | ------------------- |
| id        | INT   | Primary key         |
| from_city | INT   | Starting city ID    |
| to_city   | INT   | Destination city ID |
| cost      | FLOAT | Path cost (g(n))    |

### Table: `results`

| Column     | Type         | Description          |
| ---------- | ------------ | -------------------- |
| id         | INT          | Primary key          |
| start_city | VARCHAR(100) | Source city          |
| end_city   | VARCHAR(100) | Destination city     |
| total_cost | FLOAT        | Total cost (f(n))    |
| path       | TEXT         | Final route sequence |

---

## ⚙️ Installation & Setup

### Prerequisites

* [XAMPP](https://www.apachefriends.org/index.html) or WAMP (for PHP + MySQL)
* Python 3.x installed and added to PATH
* phpMyAdmin access

### Steps

1. Clone the repository

   ```bash
   git clone https://github.com/yourusername/Astar-WebApp.git
   ```
2. Move it to your XAMPP `htdocs` folder.
3. Start **Apache** and **MySQL** from XAMPP Control Panel.
4. Import `database.sql` in phpMyAdmin.
5. Update your MySQL credentials inside all PHP files (`localhost`, `root`, etc.).
6. Run the app in browser:

   ```
   http://localhost/Astar-WebApp/index.php
   ```
7. Ensure Python is executable via command line (e.g., `python a_star_api.py`).

---

## 🧠 Example

**From:** Kharar
**To:** Panchkula
**Possible Paths:**

* Kharar → Mohali → Panchkula
* Kharar → Zirakpur → Panchkula

**Result (Using A*):**
→ **Kharar → Mohali → Panchkula** with lowest f(n) = 15.5


