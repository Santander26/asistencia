<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuera de Horario - SIBCA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 3rem 2.5rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }
        h1 {
            color: #f1f5f9;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        p {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        .schedule {
            background: #0f172a;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            display: inline-block;
            margin: 0.5rem 0 1.5rem;
        }
        .schedule span {
            color: #facc15;
            font-weight: 600;
            font-size: 1rem;
        }
        .btn {
            display: inline-block;
            padding: 0.7rem 1.5rem;
            background: #3b82f6;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">&#x1f552;</div>
        <h1>Fuera de Horario</h1>
        <p>El sistema se encuentra disponible solo en horario laboral.</p>
        <div class="schedule">
            <span>Lunes a Viernes &middot; 8:00 AM &ndash; 1:00 PM</span>
        </div>
        <p>Si usted es Administrador o Director, inicie sesi&oacute;n para acceder sin restricciones.</p>
        <a href="index.php?ruta=login" class="btn">Ir al inicio de sesi&oacute;n</a>
    </div>
</body>
</html>
