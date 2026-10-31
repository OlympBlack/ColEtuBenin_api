<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activez votre compte - ColEtuBenin</title>
</head>

<body style="margin: 0; padding: 0; background-color: #F4F2EF; font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <div style="padding: 2.5rem 1rem;">
        <div style="max-width: 540px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #E0DDD8; box-shadow: 0 4px 16px rgba(0,0,0,0.04);">

            {{-- Header --}}
            <div style="background-color: #0b1f13; padding: 2rem 2.5rem; text-align: center;">
                <h2 style="color: #ffffff; margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.5px;">
                    Col<span style="color: #22c55e;">Campus</span>
                </h2>
                <p style="color: #86efac; font-size: 13px; margin: 4px 0 0; font-weight: 500;">
                    Plateforme de colocation étudiante au Bénin
                </p>
            </div>

            {{-- Corps de l'email --}}
            <div style="padding: 2.5rem 2.5rem 2rem;">
                <h1 style="font-size: 22px; font-weight: 700; color: #0F0F0F; margin: 0 0 1rem; line-height: 1.3;">
                    Bienvenue @if(!empty($userName)) {{ $userName }} @endif!
                </h1>
                <p style="font-size: 15px; color: #4b5563; margin: 0 0 1.75rem; line-height: 1.6;">
                    Merci de vous être inscrit sur <strong>ColEtuBenin</strong>. Pour des raisons de sécurité, votre compte doit être validé avant votre première connexion.
                </p>

                {{-- Bouton d'action principale si lien présent --}}
                @if(!empty($validationUrl))
                <div style="text-align: center; margin: 2rem 0;">
                    <a href="{{ $validationUrl }}"
                       target="_blank"
                       style="display: inline-block; background-color: #16a34a; color: #ffffff; font-weight: 700; font-size: 16px; text-decoration: none; padding: 14px 32px; border-radius: 10px; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.35);">
                        ✓ Valider mon compte
                    </a>
                    <p style="font-size: 12px; color: #9ca3af; margin: 10px 0 0;">
                        Ce lien sécurisé expire dans 48 heures.
                    </p>
                </div>
                @endif

                {{-- Bloc OTP alternatif --}}
                <div style="background: #F9FAFB; border: 1px dashed #D1D5DB; border-radius: 12px; padding: 1.25rem; text-align: center; margin: 2rem 0 1.5rem;">
                    <div style="font-size: 11px; font-weight: 600; letter-spacing: 0.1em; color: #6b7280; text-transform: uppercase; margin-bottom: 0.5rem;">
                        Ou utilisez ce code sur le site
                    </div>
                    <div style="font-family: 'Courier New', Courier, monospace; font-size: 32px; font-weight: 800; color: #111827; letter-spacing: 0.25em;">
                        {{ $otp }}
                    </div>
                </div>

                @if(!empty($validationUrl))
                <p style="font-size: 12px; color: #9ca3af; line-height: 1.5; margin: 1.5rem 0 0; word-break: break-all;">
                    Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
                    <a href="{{ $validationUrl }}" style="color: #16a34a; text-decoration: underline;">{{ $validationUrl }}</a>
                </p>
                @endif

                {{-- Note de sécurité --}}
                <div style="margin-top: 2rem; background: #FEF3C7; border-left: 4px solid #F59E0B; padding: 0.85rem 1rem; font-size: 12.5px; color: #92400E; border-radius: 0 8px 8px 0; line-height: 1.5;">
                    Si vous n'êtes pas à l'origine de cette inscription, ignorez simplement cet e-mail.
                </div>
            </div>

            {{-- Footer --}}
            <div style="border-top: 1px solid #F3F4F6; padding: 1.25rem 2.5rem; background: #fafafa; text-align: center;">
                <p style="font-size: 12px; color: #9ca3af; margin: 0;">
                    © {{ date('Y') }} ColEtuBenin — Tous droits réservés.
                </p>
            </div>
        </div>
    </div>
</body>

</html>