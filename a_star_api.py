from flask import Flask, request, jsonify
from flask_cors import CORS
import heapq
from math import radians, sin, cos, sqrt, atan2

app = Flask(__name__)
CORS(app)

def haversine(a, b):
    lat1, lon1 = a
    lat2, lon2 = b
    R = 6371.0
    dlat = radians(lat2 - lat1)
    dlon = radians(lon2 - lon1)
    rlat1 = radians(lat1)
    rlat2 = radians(lat2)
    aa = sin(dlat/2)**2 + cos(rlat1)*cos(rlat2)*sin(dlon/2)**2
    c = 2 * atan2(sqrt(aa), sqrt(1-aa))
    return R * c

def a_star_graph(cities, adjacency, start_id, end_id):
    start = str(start_id)
    goal = str(end_id)

    if start not in cities or goal not in cities:
        return {"error":"start or end not found"}

    def heuristic(node):
        entry = cities[node]
        if 'h' in entry and entry['h'] is not None:
            return float(entry['h'])
        # straight-line distance to goal
        a = (entry['lat'], entry['lng'])
        b = (cities[goal]['lat'], cities[goal]['lng'])
        return haversine(a,b)

    g_score = {start: 0.0}
    f0 = g_score[start] + heuristic(start)
    open_heap = []
    counter = 0
    heapq.heappush(open_heap, (f0, counter, start))
    came_from = {}
    closed = set()
    visited_order = []
    node_stats = {}

    while open_heap:
        fcurr, _, current = heapq.heappop(open_heap)
        if current in closed:
            continue
        closed.add(current)
        visited_order.append(current)
        node_stats[current] = {"g": g_score[current], "h": heuristic(current), "f": fcurr}

        if current == goal:
          
            path = []
            node = current
            while node in came_from:
                path.append(node)
                node = came_from[node]
            path.append(start)
            path.reverse()
            return {"visited": visited_order, "path": path, "cost": round(g_score[goal],2), "node_stats": node_stats}

        for neighbor, dist in adjacency.get(current, []):
            if neighbor in closed:
                continue
            tentative_g = g_score[current] + float(dist)
            if tentative_g < g_score.get(neighbor, float('inf')):
                came_from[neighbor] = current
                g_score[neighbor] = tentative_g
                fneighbor = tentative_g + heuristic(neighbor)
                counter +=1
                heapq.heappush(open_heap, (fneighbor, counter, neighbor))
                node_stats[neighbor] = {"g": g_score[neighbor], "h": heuristic(neighbor), "f": fneighbor}

    return {"visited": visited_order, "path": [], "cost": None, "message":"no path"}
@app.route('/api/solve', methods=['POST'])
def api_solve():
    data = request.get_json(force=True)
    raw_cities = data.get("cities", {})
    raw_edges = data.get("edges", [])
    start = data.get("start")
    end = data.get("end")
    cities = {}
    for k,v in raw_cities.items():
        key = str(k)
        cities[key] = {
            "name": v.get("name"),
            "lat": float(v.get("lat")),
            "lng": float(v.get("lng")),
            "h": None if v.get("h") is None else float(v.get("h"))
        }

    adjacency = {k:[] for k in cities.keys()}
    for e in raw_edges:
        a = str(e.get("from"))
        b = str(e.get("to"))
        if a not in cities or b not in cities:
            continue
        if "distance" in e and e["distance"] is not None:
            dist = float(e["distance"])
        else:
            dist = haversine((cities[a]["lat"], cities[a]["lng"]), (cities[b]["lat"], cities[b]["lng"]))
        adjacency[a].append((b, dist))
        # undirected graph
        if e.get("undirected", True):
            adjacency[b].append((a, dist))

    result = a_star_graph(cities, adjacency, start, end)
    return jsonify(result)

@app.route("/health")
def health():
    return jsonify({"status":"ok"})

if __name__=="__main__":
    app.run(port=5001, debug=True)
