<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WarisanMakan</title>
    <style>
        :root {
            --red: #8c1f1f;
            --red-deep: #6f1717;
            --cream: #f7efe5;
            --cream-2: #efe0c8;
            --gold: #c9971d;
            --text: #3d2b21;
            --muted: #6b4d3d;
            --line: #d8c0a4;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--cream);
            color: var(--text);
            line-height: 1.6;
        }

        .page {
            max-width: 1240px;
            margin: 0 auto;
            padding: 20px;
        }

        .hero, .card, .footer {
            background: #fbf5eb;
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(70, 30, 10, 0.06);
        }

        .hero {
            padding: 28px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #fdf8ef 0%, #f3e4c8 100%);
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: linear-gradient(120deg, rgba(140,31,31,0.08) 0%, rgba(140,31,31,0.04) 100%);
            pointer-events: none;
        }

        .hero-content { position: relative; z-index: 1; }

        .eyebrow {
            display: inline-block;
            background: rgba(201,151,29,0.16);
            color: var(--red-deep);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        h1, h2, h3 {
            font-family: Georgia, "Times New Roman", serif;
            margin: 0 0 10px;
            color: var(--red-deep);
        }

        h1 { font-size: clamp(28px, 4vw, 46px); line-height: 1.1; }
        h2 { font-size: clamp(22px, 3vw, 30px); }
        h3 { font-size: 18px; }

        p { margin: 0 0 12px; color: var(--muted); }
        .btn {
            display: inline-block;
            background: var(--gold);
            color: #fff;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 999px;
            font-weight: 700;
            margin-top: 8px;
        }

        .hero-grid {
            display: grid;
            gap: 20px;
            margin-top: 22px;
        }

        .hero-visual {
            min-height: 260px;
            background: linear-gradient(135deg, rgba(140,31,31,0.95), rgba(109,20,20,0.9));
            border-radius: 20px;
            padding: 24px;
            color: #fff;
            display: flex;
            align-items: flex-end;
            position: relative;
            overflow: hidden;
        }

        .hero-visual::after {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 40%);
        }

        .hero-visual .inner {
            position: relative;
            z-index: 1;
        }

        .section { margin-top: 20px; }

        .card {
            padding: 22px;
            margin-top: 16px;
        }

        .grid { display: grid; gap: 16px; }
        .grid-2 { grid-template-columns: 1fr; }

        .pill {
            display: inline-block;
            background: rgba(140,31,31,0.08);
            color: var(--red-deep);
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            margin-right: 8px;
            margin-bottom: 8px;
        }

        .stat-box {
            background: var(--cream);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px;
        }

        .footer {
            padding: 20px 24px;
            margin: 20px 0 8px;
            text-align: center;
        }

        @media (min-width: 768px) {
            .hero-grid { grid-template-columns: 1.1fr 0.9fr; align-items: center; }
            .grid-2 { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
<div class="page">
    <section class="hero">
        <div class="hero-content">
            <div class="eyebrow">Heritage • Discovery • Preservation</div>
            <h1>Preserve the stories behind Malaysia's most treasured food traditions.</h1>
            <p>WarisanMakan helps locals and tourists uncover heritage food vendors, collect memorable food journeys, and celebrate the people who keep tradition alive.</p>
            <a class="btn" href="/heritage-shops">Explore Heritage Food</a>
        </div>

        <div class="hero-grid">
            <div class="hero-visual">
                <div class="inner">
                    <h3>From kopitiam to kampung kitchen</h3>
                    <p>Discover timeless recipes, founder stories, and hidden culinary landmarks across Malaysia.</p>
                </div>
            </div>
            <div class="card" style="margin-top:0;">
                <h3>Featured heritage experiences</h3>
                <p><span class="pill">Founder stories</span><span class="pill">Establishment years</span><span class="pill">Traditional dishes</span></p>
                <p>Explore participating vendors with rich histories, local food significance, and deep cultural roots.</p>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="card">
            <h2>Heritage Shop Discovery</h2>
            <p>Search and browse traditional food vendors with founder stories, establishment years, and cultural context.</p>
            <div class="grid grid-2">
                <div class="stat-box">
                    <h3>Search by place, category, or keyword</h3>
                    <p>Browse vendors that preserve classic recipes and neighborhood food traditions.</p>
                </div>
                <div class="stat-box">
                    <h3>Stories that matter</h3>
                    <p>Each listing highlights heritage, lineage, and the food that defines the shop.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="card">
            <h2>Food Passport & Achievement</h2>
            <p>Check in at heritage spots, unlock meaningful badges, and track your journey through Malaysia's culinary landscape.</p>
            <div class="grid grid-2">
                <div class="stat-box">
                    <h3>GPS check-ins</h3>
                    <p>Record visits and build your personal food trail across the country.</p>
                </div>
                <div class="stat-box">
                    <h3>Leaderboards</h3>
                    <p>Celebrate discovery with a rewarding system designed for exploration, not gamification overload.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="card">
            <h2>Food Trail Generator</h2>
            <p>Plan a personalized route between heritage vendors with a simple, elegant map-inspired experience.</p>
            <div class="stat-box">
                <h3>Suggested route preview</h3>
                <p>Discover a curated path that connects flavours, towns, and cultural stories in one journey.</p>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="card">
            <h2>Blind Box Recommendation</h2>
            <p>Get a surprise heritage food recommendation and uncover a new favourite through playful discovery.</p>
            <div class="stat-box">
                <h3>Surprise your next stop</h3>
                <p>Let the app introduce a hidden gem when you are ready for a new culinary adventure.</p>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="card">
            <h2>Community Contribution</h2>
            <p>Share heritage shop information, founder notes, and family history to help preserve Malaysia's living culinary culture.</p>
            <a class="btn" href="/heritage-shops">Share Shop Information</a>
        </div>
    </section>

    <footer class="footer">
        <p style="margin:0;">WarisanMakan © 2026 · Heritage food discovery for Malaysia.</p>
    </footer>
</div>
</body>
</html>
