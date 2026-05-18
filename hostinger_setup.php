<?php

/**
 * =============================================================================
 * HOSTINGER DEPLOYMENT SETUP — Project Purisima (Twilio SMS + Queue Worker)
 * =============================================================================
 *
 * Run this file ONCE via SSH or Hostinger's File Manager terminal after
 * uploading the project. It does NOT modify production data — it only prints
 * the commands and config values you need to set up.
 *
 * Usage (SSH):
 *   php hostinger_setup.php
 *
 * =============================================================================
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║        Project Purisima — Hostinger Deployment Setup         ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// ─── 1. .env variables required ──────────────────────────────────────────────

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  STEP 1 — Add these variables to your .env file\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$envVars = [
    '# ── Twilio SMS ─────────────────────────────────────────────' => null,
    'TWILIO_ACCOUNT_SID'  => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'TWILIO_AUTH_TOKEN'   => 'your_twilio_auth_token_here',
    'TWILIO_FROM_NUMBER'  => '+1XXXXXXXXXX',

    '# ── Queue (use database driver on shared hosting) ──────────' => null,
    'QUEUE_CONNECTION'    => 'database',

    '# ── App (make sure this is set to production) ──────────────' => null,
    'APP_ENV'             => 'production',
    'APP_DEBUG'           => 'false',

    '# ── Google OAuth ───────────────────────────────────────────' => null,
    'GOOGLE_CLIENT_ID'    => 'your_google_client_id',
    'GOOGLE_CLIENT_SECRET'=> 'your_google_client_secret',
    'GOOGLE_REDIRECT_URI' => 'https://yourdomain.com/auth/google/callback',

    '# ── OpenAI ─────────────────────────────────────────────────' => null,
    'OPENAI_API_KEY'      => 'sk-...',
    'OPENAI_MODEL'        => 'gpt-4o',
];

foreach ($envVars as $key => $value) {
    if ($value === null) {
        echo "  {$key}\n";
    } else {
        echo "  {$key}={$value}\n";
    }
}

echo "\n";

// ─── 2. Twilio dashboard instructions ────────────────────────────────────────

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  STEP 2 — Twilio Console Setup (console.twilio.com)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "  1. Sign in at https://console.twilio.com\n";
echo "  2. Copy Account SID  → set as TWILIO_ACCOUNT_SID in .env\n";
echo "  3. Copy Auth Token   → set as TWILIO_AUTH_TOKEN in .env\n";
echo "  4. Buy a phone number (Messaging > Phone Numbers > Buy)\n";
echo "     - Choose a number with SMS capability\n";
echo "     - For Philippine recipients, an international-capable US/SG number works\n";
echo "  5. Set TWILIO_FROM_NUMBER to your purchased number (+1XXXXXXXXXX)\n";
echo "  6. If testing with a Trial account:\n";
echo "     - Verify recipient numbers at Twilio > Verified Caller IDs\n";
echo "     - Trial accounts can only send to verified numbers\n\n";

// ─── 3. Artisan commands to run via SSH ──────────────────────────────────────

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  STEP 3 — Run these Artisan commands via SSH\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$commands = [
    'Install PHP dependencies'         => 'composer install --no-dev --optimize-autoloader',
    'Install Node + build assets'      => 'npm ci && npm run build',
    'Generate app key (first time)'    => 'php artisan key:generate',
    'Run all migrations'               => 'php artisan migrate --force',
    'Create storage symlink'           => 'php artisan storage:link',
    'Cache config for performance'     => 'php artisan config:cache',
    'Cache routes for performance'     => 'php artisan route:cache',
    'Cache views for performance'      => 'php artisan view:cache',
];

foreach ($commands as $description => $command) {
    echo "  # {$description}\n";
    echo "  {$command}\n\n";
}

// ─── 4. Cron job for queue worker (Hostinger panel) ──────────────────────────

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  STEP 4 — Set up Cron Job in Hostinger Control Panel\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "  Go to: Hostinger hPanel > Advanced > Cron Jobs\n\n";
echo "  Add TWO cron jobs:\n\n";
echo "  ┌─────────────────────────────────────────────────────────┐\n";
echo "  │  Cron 1: Process queued SMS (every minute)              │\n";
echo "  │  Schedule : * * * * *                                   │\n";
echo "  │  Command  : cd /home/USERNAME/domains/YOURDOMAIN/public_html && \\\n";
echo "  │             php artisan queue:work --queue=sms,default \\\n";
echo "  │             --tries=3 --timeout=60 --stop-when-empty    │\n";
echo "  └─────────────────────────────────────────────────────────┘\n\n";
echo "  ┌─────────────────────────────────────────────────────────┐\n";
echo "  │  Cron 2: Laravel scheduler (every minute)               │\n";
echo "  │  Schedule : * * * * *                                   │\n";
echo "  │  Command  : cd /home/USERNAME/domains/YOURDOMAIN/public_html && \\\n";
echo "  │             php artisan schedule:run >> /dev/null 2>&1  │\n";
echo "  └─────────────────────────────────────────────────────────┘\n\n";
echo "  Replace USERNAME and YOURDOMAIN with your actual values.\n";
echo "  You can find the full path in Hostinger > Files > File Manager\n\n";

// ─── 5. Verify Twilio is working ─────────────────────────────────────────────

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  STEP 5 — Test SMS after deployment (run via SSH)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "  # Send a test SMS immediately (replace +63XXXXXXXXXX with your number)\n";
echo "  php artisan tinker --execute=\"app(App\Services\SmsService::class)->sendNow('+63XXXXXXXXXX', 'Test from Project Purisima');\"\n\n";
echo "  # Check the outbound SMS log table\n";
echo "  php artisan tinker --execute=\"App\Models\SmsOutboundMessage::latest()->take(5)->get(['to','status','sent_at','error_message']);\"\n\n";
echo "  # Check the queue for any pending SMS jobs\n";
echo "  php artisan tinker --execute=\"DB::table('jobs')->where('queue','sms')->count();\"\n\n";

// ─── 6. SMS features summary ─────────────────────────────────────────────────

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  SMS FEATURES INTEGRATED\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "  ✓ Transaction status changes (in_progress / completed / rejected / cancelled)\n";
echo "  ✓ Payment confirmation: SMS sent to resident when payment is processed (receipt # included)\n";
echo "  ✓ Forgot-password OTP: residents can reset password via SMS code (/forgot-password/phone)\n";
echo "  ✓ OTP / verification codes (SmsOtpService::send / verify)\n";
echo "  ✓ Mass broadcast to all residents (SmsBroadcastService::broadcastToResidents)\n";
echo "  ✓ All SMS queued via SendSmsJob (queue=sms, 3 retries, exponential backoff)\n";
echo "  ✓ Every outbound message logged in sms_outbound_messages table\n";
echo "  ✓ Local/testing environments use LogSmsGateway (no real SMS sent)\n\n";

echo "  SERVICE USAGE EXAMPLES:\n\n";
echo "  // Send a queued SMS (non-blocking)\n";
echo "  app(SmsService::class)->send('+63XXXXXXXXXX', 'Hello!', 'transaction', 42);\n\n";
echo "  // Send OTP\n";
echo "  app(SmsOtpService::class)->send('+63XXXXXXXXXX', 'verification');\n\n";
echo "  // Verify OTP\n";
echo "  app(SmsOtpService::class)->verify('+63XXXXXXXXXX', '123456', 'verification');\n\n";
echo "  // Mass broadcast\n";
echo "  app(SmsBroadcastService::class)->broadcastToResidents('Alert', 'Message body', \$user);\n\n";

echo "════════════════════════════════════════════════════════════════\n";
echo "  Setup complete. Delete this file after use for security.\n";
echo "════════════════════════════════════════════════════════════════\n\n";
