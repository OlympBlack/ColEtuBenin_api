<?php

namespace App\Console\Commands;

use App\Mail\SendOtpMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email? : Adresse email de test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Teste l\'envoi d\'un email OTP en affichant la configuration active';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $recipient = $this->argument('email') ?? config('mail.from.address');

        $this->info("=== Diagnostic Configuration Email ===");
        $this->line("Mailer par défaut : " . config('mail.default'));
        $this->line("Hôte SMTP         : " . config('mail.mailers.smtp.host'));
        $this->line("Port SMTP         : " . config('mail.mailers.smtp.port'));
        $this->line("Nom d'utilisateur : " . config('mail.mailers.smtp.username'));
        $this->line("Adresse Expéditeur: " . config('mail.from.address'));
        $this->line("Nom Expéditeur    : " . config('mail.from.name'));
        $this->newLine();

        if (config('mail.default') === 'log') {
            $this->warn("ATTENTION : Le pilote actuel est 'log' ! Les emails sont écrits dans storage/logs/laravel.log et ne sont PAS envoyés sur le réseau.");
            $this->warn("Veuillez définir MAIL_MAILER=smtp dans votre fichier .env sur le serveur.");
            $this->newLine();
        }

        $this->info("Tentative d'envoi d'un email de test vers : {$recipient} ...");

        try {
            $validationUrl = config('app.frontend_url', 'https://coletubenin.lavoixdabouloussi.org') . '/#/connexion?validated=1';
            $otp = (string) random_int(100000, 999999);

            Mail::to($recipient)->send(new SendOtpMail($otp, $validationUrl, 'Testeur ColEtuBenin'));

            $this->newLine();
            $this->info("✓ SUCCÈS : L'email avec le code OTP {$otp} a été envoyé avec succès à {$recipient} !");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error("✗ ÉCHEC lors de l'envoi de l'email :");
            $this->error($e->getMessage());
            $this->newLine();
            $this->line("Trace : " . $e->getFile() . ':' . $e->getLine());
            return Command::FAILURE;
        }
    }
}
