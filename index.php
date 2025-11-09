<?php
define('DB_HOST','127.0.0.1');
define('DB_NAME','a_star_db');
define('DB_USER','root');
define('DB_PASS',''); 
try {
  $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);
} catch (PDOException $e) {
  die("DB error: " . $e->getMessage());
}
$cities = $pdo->query("SELECT * FROM cities ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$edges = $pdo->query("SELECT e.*, cfrom.name as from_name, cto.name as to_name
                      FROM edges e
                      JOIN cities cfrom ON e.from_city=cfrom.id
                      JOIN cities cto ON e.to_city=cto.id
                      ORDER BY e.id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <title>Dynamic A* (PHP frontend with Video Background)</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <style>
    body {margin:0; font-family: Arial, sans-serif;}
    #bgVideo {
      position: fixed;
      top:0; left:0;
      width:100%; height:100%;
      object-fit:cover;
      z-index:-1;
    }
    #mainContainer {
      position: relative;
      z-index:1;
      opacity:0;
      animation: fadeIn 1.2s forwards;
      padding:10px;
    }
    @keyframes fadeIn { to { opacity: 1; } }
    .panel {
      background: rgba(255,255,255,0.85);
      padding:12px;
      border-radius:8px;
      box-shadow:0 4px 12px rgba(0,0,0,0.15);
      margin-bottom:8px;
    }
    label{display:block; margin-top:6px;}
    #map{height:520px;width:100%;}
    button {
      transition: 0.3s;
      cursor:pointer;
      padding:6px 10px;
      border:none;
      border-radius:6px;
      background:#3388ff;
      color:#fff;
      font-weight:bold;
    }
    button:hover {
      transform: scale(1.05);
      background-color:#ff9800;
    }
    ul{padding-left:18px;}
  </style>
</head>
<body>

<video autoplay muted loop id="bgVideo">
  <source src="v/s.mp4" type="video/mp4">
  Your browser does not support the video tag.
</video>

<div id="mainContainer">
  <h2>Dynamic A* — Add cities & paths, run algorithm</h2>

  <div style="display:flex;gap:12px;">
    <div style="width:380px;">
      <div class="panel">
        <h3>Add City</h3>
        <form id="addCityForm" method="POST" action="add_city.php">
          <label>Name: <input name="name" required></label>
          <label>Lat: <input name="lat" required></label>
          <label>Lng: <input name="lng" required></label>
          <label>Heuristic (optional): <input name="h"></label>
          <button type="submit">Add City</button>
        </form>
      </div>

      <div class="panel">
        <h3>Add Edge (path)</h3>
        <form id="addEdgeForm" method="POST" action="add_edge.php">
          <label>From:
            <select name="from_city" required>
              <?php foreach($cities as $c) echo "<option value='{$c['id']}'>{$c['id']} - ".htmlspecialchars($c['name'])."</option>"; ?>
            </select>
          </label>
          <label>To:
            <select name="to_city" required>
              <?php foreach($cities as $c) echo "<option value='{$c['id']}'>{$c['id']} - ".htmlspecialchars($c['name'])."</option>"; ?>
            </select>
          </label>
          <label>Distance (km, optional — leave blank to compute straight-line): <input name="distance"></label>
          <label><input type="checkbox" name="undirected" checked> Undirected</label>
          <button type="submit">Add Edge</button>
        </form>
      </div>

      <div class="panel">
        <h3>Existing Cities</h3>
        <ul id="cityList">
          <?php foreach($cities as $c) echo "<li>{$c['id']} - ".htmlspecialchars($c['name'])." ({$c['lat']},{$c['lng']}) h=".($c['heuristic']===null?'NULL':$c['heuristic'])."</li>"; ?>
        </ul>
        <h3>Existing Edges</h3>
        <ul id="edgeList">
          <?php foreach($edges as $e) echo "<li>{$e['id']}: {$e['from_name']} → {$e['to_name']} = {$e['distance']} km</li>"; ?>
        </ul>
      </div>
    </div>

    <div style="flex:1;">
      <div class="panel">
        <h3>Controls</h3>
        <label>Start:
          <select id="startSelect">
            <?php foreach($cities as $c) echo "<option value='{$c['id']}'>{$c['id']} - ".htmlspecialchars($c['name'])."</option>"; ?>
          </select>
        </label>
        <label>End:
          <select id="endSelect">
            <?php foreach($cities as $c) echo "<option value='{$c['id']}'>{$c['id']} - ".htmlspecialchars($c['name'])."</option>"; ?>
          </select>
        </label>
        <label><input type="checkbox" id="useSLH" checked> Use straight-line heuristic if missing</label>
        <button id="solveBtn">Find Best Path</button>
        <button id="saveResultBtn" disabled>Save Result</button>
        <span id="status" style="margin-left:8px"></span>
      </div>

      <div id="map" class="panel"></div>

      <div class="panel" id="resultPanel" style="display:none;">
        <h3>Result</h3>
        <div id="resultInfo"></div>
        <pre id="rawJson" style="background:#f7f7f7;padding:8px;border-radius:4px;max-height:160px;overflow:auto"></pre>
      </div>
    </div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('map').setView([30.7,76.75],12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

const CITIES = <?php
  $cmap = [];
  foreach($cities as $c){
    $cmap[$c['id']] = ['name'=>$c['name'],'lat'=>floatval($c['lat']),'lng'=>floatval($c['lng']),'h'=>$c['heuristic']===null?null:floatval($c['heuristic'])];
  }
  echo json_encode($cmap);
?>;

const EDGES = <?php
  $elist = [];
  foreach($edges as $e){
    $elist[] = ['from'=>$e['from_city'],'to'=>$e['to_city'],'distance'=>floatval($e['distance'])];
  }
  echo json_encode($elist);
?>;

const markers = {};
for(const id in CITIES){
  const c = CITIES[id];
  const m = L.circleMarker([c.lat,c.lng],{radius:8,fillColor:'#3388ff',color:'#fff',weight:1,fillOpacity:0.9}).addTo(map)
    .bindPopup(`${id} - ${c.name}`);
  m.on('mouseover',()=>m.setStyle({fillColor:'orange', radius:12}));
  m.on('mouseout',()=>m.setStyle({fillColor:'#3388ff', radius:8}));
  markers[id] = m;
}

const edgeLayers = [];
for(const e of EDGES){
  const a=CITIES[e.from], b=CITIES[e.to];
  if(a && b){
    const line = L.polyline([[a.lat,a.lng],[b.lat,b.lng]], {color:'#999',weight:2,opacity:0.6}).addTo(map);
    edgeLayers.push(line);
  }
}

let lastResult=null, routeLayer=null;
document.getElementById('solveBtn').addEventListener('click', async ()=>{
  const start = parseInt(document.getElementById('startSelect').value);
  const end = parseInt(document.getElementById('endSelect').value);
  if(!start || !end || start===end){ alert('Choose different start and end'); return; }
  document.getElementById('status').textContent='Solving...';
  const citiesPayload={}, edgesPayload=[];
  for(const id in CITIES) citiesPayload[id]={name:CITIES[id].name, lat:CITIES[id].lat, lng:CITIES[id].lng, h:CITIES[id].h};
  for(const e of EDGES) edgesPayload.push({from:e.from,to:e.to,distance:e.distance,undirected:true});
  const payload={cities:citiesPayload, edges:edgesPayload, start, end, use_straight_line_heuristic_if_missing:document.getElementById('useSLH').checked};
  try{
    const resp=await fetch('http://127.0.0.1:5001/api/solve', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)});
    const result=await resp.json();
    lastResult=result;
    document.getElementById('status').textContent=result.message || 'Done';
    document.getElementById('resultPanel').style.display='block';
    document.getElementById('rawJson').textContent=JSON.stringify(result,null,2);
    if(routeLayer){ map.removeLayer(routeLayer); routeLayer=null; }
    if(result.path && result.path.length>0){
      const coords=result.path.map(id=>[CITIES[id].lat, CITIES[id].lng]);
      routeLayer=L.polyline(coords,{color:'orange',weight:5}).addTo(map);
      map.fitBounds(routeLayer.getBounds(),{padding:[20,20]});
      document.getElementById('resultInfo').innerHTML=`<b>Path:</b> ${result.path.join(' → ')} <br><b>Cost:</b> ${result.cost}`;
      document.getElementById('saveResultBtn').disabled=false;
    } else {
      document.getElementById('resultInfo').innerHTML='<i>No path found</i>';
      document.getElementById('saveResultBtn').disabled=true;
    }
  }catch(e){ alert('Failed to call backend. Make sure Flask is running at http://127.0.0.1:5001'); console.error(e); document.getElementById('status').textContent='Error'; }
});

document.getElementById('saveResultBtn').addEventListener('click', async ()=>{
  if(!lastResult || !lastResult.path) return;
  const start=parseInt(document.getElementById('startSelect').value);
  const end=parseInt(document.getElementById('endSelect').value);
  const payload={start,end,path:lastResult.path,cost:lastResult.cost,node_stats:lastResult.node_stats};
  const resp=await fetch('save_result.php',{method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)});
  const res=await resp.json();
  if(res.success) alert('Saved result id: '+res.id);
  else alert('Save failed: '+(res.error||'unknown'));
});
</script>
</body>
</html>
