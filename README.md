# Admin Dashboard

Laravel 13, PHP 8.4, Fortify, Sanctum, Spatie Permission/Activitylog, Vite 8 এবং Tailwind 4 দিয়ে তৈরি admin dashboard।

## স্থানীয় setup

PHP 8.4, Composer 2 এবং Node.js 22.12+ ব্যবহার করুন। Existing project-এর `.env` overwrite করবেন না।

```sh
composer install
npm ci --ignore-scripts
```

নতুন installation হলে `.env.example` থেকে `.env` তৈরি করুন। Database connection, APP_URL এবং mail configuration দিন। কেবল নতুন installation-এ application key তৈরি করুন:

```sh
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
npm run build
```

Existing installation-এর APP_KEY পরিবর্তন করবেন না—এতে encrypted 2FA secrets ও cookies অকার্যকর হবে। Laravel Herd স্থানীয় site serve করে; আলাদা server চালানো প্রয়োজন নেই।

## Authentication ও permissions

- Public registration admission open থাকলে student role দেয়। প্রতি IP-তে সর্বোচ্চ ৫ attempts/minute এবং ২০/hour।
- Login-এর জন্য active account এবং admin pages-এর জন্য verified email প্রয়োজন।
- Profile page খোলার আগে password confirmation লাগে, কারণ সেখানে 2FA QR/recovery codes থাকে।
- Admin user-edit দিয়ে নিজের account পরিবর্তন করা যায় না; নিজের তথ্য profile থেকে পরিবর্তন করুন। Email বদলাতে current password প্রয়োজন।
- Password reset ও password change পুরোনো Sanctum tokens বাতিল করে।
- Deactivation, deletion বা administrator-এর email change session version বদলায়। ফলে database/file/Redis session-ও পরের request-এ বাতিল হয়। এই version check যুক্ত হওয়ার আগে তৈরি browser sessions-এ আবার login লাগবে।
- Non-super-admin role manager কেবল নিজের permission-এর সীমার role এবং manage করতে পারে এমন সদস্যদের shared role পরিবর্তন করতে পারে। Protected student role শুধু super-admin পরিবর্তন করতে পারে। Super-admin role পরিবর্তনযোগ্য নয়।
- Passkeys এখন বন্ধ। Contract, frontend ceremony এবং device tests সম্পূর্ণ না করে চালু করবেন না। বিদ্যমান passkeys table/data মুছে ফেলা হয়নি।
- Admission form নির্দিষ্ট is_on boolean পাঠায়; একই close request দুবার দিলে registration পুনরায় খুলবে না। Settings/admission state সরাসরি database থেকে পড়া হয়।
- Invitation mail synchronous; database commit-এর পরে পাঠানো হয়। Transport ব্যর্থ হলে account থাকে এবং user list থেকে resend করা যায়। Mail server acceptance inbox delivery-এর নিশ্চয়তা নয়।

## Production deployment

Deploy করার আগে database ও `storage/app/public` backup নিন; backup restore rehearsal করুন। Production configuration:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
MAIL_MAILER=smtp
SUPER_ADMIN_EMAIL=your-admin@example.com
SUPER_ADMIN_PASSWORD=replace-with-a-unique-long-password
```

প্রকৃত credentials repository-তে রাখবেন না। Mail host/user/password এবং database/cache/session connection production service অনুযায়ী দিন। Reverse proxy থাকলে trusted proxy/HTTPS configuration যাচাই করুন। Web root অবশ্যই `public/`; uploads directory-তে script execution বন্ধ রাখুন।

প্রথম provisioning-এ admin seed করুন। Existing installation-এ seed পুনরায় চালানো existing admin password বদলায় না। Default `password` নিয়ে production seed application প্রত্যাখ্যান করে। Admin-এর 2FA চালু করে recovery codes নিরাপদে সংরক্ষণ করুন।

Dependency install এবং build শেষ করে release-এর আগে migrations চালান:

```sh
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci --ignore-scripts
npm run build
php artisan migrate --force --no-interaction
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

নতুন migrations activity_log.created_at index এবং users.auth_version যোগ করে; existing data delete করে না। Code switch-এর আগে schema প্রস্তুত রাখুন; প্রয়োজনে maintenance window নিন। Populated production database-এ `migrate:fresh`, `migrate:refresh` বা blind rollback চালাবেন না। Original activity-table migration rollback টেবিল ও logs মুছে দেয়। Schema rollback-এর আগে corresponding old code ফিরিয়ে দিতে হবে।

Scheduler প্রতি মিনিটে চালাতে হবে:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

এতে ১৮০ দিনের বেশি পুরোনো activity logs পরিষ্কার এবং expired reset tokens cleanup হয়। Log retention business requirement অনুযায়ী নির্ধারণ করুন। বড় database-এ cleanup query/lock duration পর্যবেক্ষণ করুন। অন্য queued কাজ যোগ করলে supervised worker চালান এবং deploy-এর পরে restart করুন:

```sh
php artisan queue:work --tries=3 --timeout=60
php artisan queue:restart
php artisan queue:failed
```

Monitor: application exceptions, failed jobs, SMTP failure, disk space, database latency, backup freshness এবং scheduler heartbeat। `storage` ও `bootstrap/cache` web process-এর writable হতে হবে।

## যাচাই

Disposable test database হিসেবে phpunit.xml SQLite :memory: ব্যবহার করে; production database নয়। Tests চালানোর আগে frontend build করুন:

```sh
composer validate --strict --no-check-publish
composer audit --locked
npm run build
php artisan test --compact
```

GitHub Actions workflow একই checks চালায়। Audit regression tests password-reset token revocation, profile secret protection, self-email bypass, passkey route disablement, upload failure/cleanup, mail failure, registration throttling, admission idempotence, shared-role boundaries, permission form state, rollback, settings permissions, session version এবং date-filter boundary cover করে।

Release smoke check: login/logout, email verification/reset, invitation/resend, 2FA enable/confirm/login, user/role lifecycle, settings uploads, keyboard navigation, mobile এবং light/dark appearance। Real SMTP, browser/WebAuthn, deployed MySQL concurrency এবং external penetration testing আলাদাভাবে যাচাই করতে হবে। Tests পাস করলেও ভবিষ্যতে dependency/security updates প্রয়োজন হবে।