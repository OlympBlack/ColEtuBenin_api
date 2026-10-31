<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $success ? 'Compte validé avec succès' : 'Erreur de validation' }} - ColEtuBenin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #f8fafc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            max-width: 480px;
            width: 100%;
            padding: 40px 32px;
            text-align: center;
        }
        .icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }
        .icon-success {
            background-color: #dcfce7;
            color: #16a34a;
        }
        .icon-error {
            background-color: #fee2e2;
            color: #dc2626;
        }
        h1 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        p {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            width: 100%;
            padding: 14px 24px;
            background-color: #16a34a;
            color: #ffffff;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.3);
        }
        .btn:hover {
            background-color: #15803d;
            transform: translateY(-1px);
        }
        .secondary-link {
            display: inline-block;
            margin-top: 18px;
            font-size: 13px;
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
        }
        .secondary-link:hover {
            color: #0f172a;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="card">
        @if($success)
            <div class="icon-wrapper icon-success">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <h1>Compte validé avec succès !</h1>
            <p>{{ $message }} Vous pouvez maintenant vous connecter à votre compte ColEtuBenin et commencer à explorer les annonces ou publier.</p>
            <a href="{{ $loginUrl }}" class="btn">Se connecter à ColEtuBenin</a>
        @else
            <div class="icon-wrapper icon-error">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
            <h1>Validation impossible</h1>
            <p>{{ $message }}</p>
            <a href="{{ $loginUrl }}" class="btn">Aller à la page de connexion</a>
        @endif
        <div>
            <a href="https://colcampus.lavoixdabouloussi.org" class="secondary-link">← Retourner à l'accueil ColEtuBenin</a>
        </div>
    </div>
</body>
</html>
