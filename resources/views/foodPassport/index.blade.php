<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Food Passport</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; padding: 2rem; }
        .card { border: 1px solid #e5e7eb; padding: 1rem; border-radius: 6px; max-width:720px }
        label{display:block;margin-top:.5rem}
        input[type=text], input[type=number]{width:100%;padding:.5rem;margin-top:.25rem}
        button{margin-top:1rem;padding:.5rem 1rem}
        pre{background:#f8fafc;padding:1rem;border-radius:6px;overflow:auto}
    </style>
</head>
<body>
    <div class="card">
        <h1>Food Passport — Check In</h1>

        <p>Use this form to simulate a check-in for a shop. In production the shop list would be provided by the server.</p>

        <label>Shop ID
            <input id="shop_id" type="number" value="1">
        </label>

        <label>Shop Latitude
            <input id="shop_lat" type="text" value="3.123456">
        </label>

        <label>Shop Longitude
            <input id="shop_lng" type="text" value="101.123456">
        </label>

        <label>Permitted Radius (meters)
            <input id="radius" type="number" value="100">
        </label>

        <label><input id="is_participating" type="checkbox" checked> Participating</label>
        <label><input id="is_published" type="checkbox" checked> Published</label>

        <div>
            <button id="btnCheckIn">Request Check-In (use browser geolocation)</button>
        </div>

        <h3>Result</h3>
        <pre id="result">No attempt yet.</pre>
    </div>

<script>
(function(){
    const btn = document.getElementById('btnCheckIn');
    const out = document.getElementById('result');

    function setResult(obj){
        out.textContent = typeof obj === 'string' ? obj : JSON.stringify(obj, null, 2);
    }

    btn.addEventListener('click', () => {
        if (!navigator.geolocation) {
            setResult('Geolocation is not supported by this browser.');
            return;
        }

        setResult('Requesting location...');

        navigator.geolocation.getCurrentPosition(success, error, { enableHighAccuracy: true, timeout: 10000 });

        function error(err){
            setResult('Failed to get location: ' + (err.message || err.code));
        }

        async function success(position){
            const userLat = position.coords.latitude;
            const userLng = position.coords.longitude;

            const payload = {
                shop_id: Number(document.getElementById('shop_id').value),
                shop_latitude: parseFloat(document.getElementById('shop_lat').value),
                shop_longitude: parseFloat(document.getElementById('shop_lng').value),
                user_latitude: userLat,
                user_longitude: userLng,
                radius_meters: Number(document.getElementById('radius').value),
                is_participating: document.getElementById('is_participating').checked,
                is_published: document.getElementById('is_published').checked
            };

            setResult('Sending check-in request...');

            try {
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('/passport/check-in', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const json = await res.json();
                setResult(json);
            } catch (e) {
                setResult('Network or server error: ' + e.message);
            }
        }
    });
})();
</script>
</body>
</html>