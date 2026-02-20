# نظام اللغات المتعدد - ZuckBook

## الملفات المنشأة:

### 1. `lang/translations.php`
ملف الترجمات الرئيسي يحتوي على:
- جميع الترجمات للعربية والإنجليزية
- دوال مساعدة: `t()`, `getCurrentLang()`, `getDir()`

### 2. `backend/update_language.php`
API endpoint لحفظ اللغة المختارة في:
- Session
- Database (جدول users)

### 3. `add_language_column.php`
سكريبت لإضافة عمود language في جدول users

## كيفية الاستخدام:

### الخطوة 1: إضافة عمود اللغة
قم بزيارة: `http://localhost/add_language_column.php`

### الخطوة 2: في أي صفحة PHP، أضف:

```php
<?php
session_start();
require_once __DIR__ . "/lang/translations.php";

// Get current language
$lang = getCurrentLang(); // 'en' or 'ar'
$dir = getDir(); // 'ltr' or 'rtl'
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
    <title><?= t('page_title') ?></title>
</head>
<body>
    <h1><?= t('welcome') ?></h1>
    <p><?= t('description') ?></p>
</body>
</html>
```

### الخطوة 3: إضافة ترجمات جديدة

في `lang/translations.php`:

```php
$translations = [
    'en' => [
        'new_key' => 'English Text',
    ],
    'ar' => [
        'new_key' => 'النص بالعربية',
    ]
];
```

### الخطوة 4: استخدام الترجمة

```php
<?= t('new_key') ?>
```

## الدوال المساعدة:

### `t($key, $lang = null)`
تحصل على الترجمة للمفتاح المحدد
```php
echo t('welcome'); // Welcome أو مرحباً
```

### `getCurrentLang()`
تحصل على اللغة الحالية
```php
$lang = getCurrentLang(); // 'en' or 'ar'
```

### `getDir()`
تحصل على اتجاه النص
```php
$dir = getDir(); // 'ltr' or 'rtl'
```

## الصفحات المحدثة:
✅ settings.php - تم تحديثها بالكامل

## الصفحات التي تحتاج تحديث:
- home.php
- profile.php
- friends.php
- groups.php
- notifications.php
- وجميع الصفحات الأخرى

## مثال كامل لتحديث صفحة:

```php
<?php
session_start();
require_once __DIR__ . "/backend/config.php";
require_once __DIR__ . "/lang/translations.php";

// Get user data and set language
$userStmt = $conn->prepare("SELECT language FROM users WHERE id = ?");
$userStmt->bind_param("i", $_SESSION['user_id']);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

$_SESSION['lang'] = $userData['language'] ?? 'en';
$lang = getCurrentLang();
$dir = getDir();
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <title><?= t('home') ?> - ZuckBook</title>
</head>
<body>
    <nav>
        <a href="/home.php"><?= t('home') ?></a>
        <a href="/friends.php"><?= t('friends') ?></a>
        <a href="/groups.php"><?= t('groups') ?></a>
    </nav>
    
    <h1><?= t('whats_on_mind') ?></h1>
    <button><?= t('create_post') ?></button>
</body>
</html>
```

## ملاحظات مهمة:

1. **RTL Support**: عند اختيار العربية، يتم تلقائياً تطبيق `dir="rtl"`
2. **Session**: اللغة محفوظة في Session للاستخدام الفوري
3. **Database**: اللغة محفوظة في قاعدة البيانات للاستمرارية
4. **Default**: اللغة الافتراضية هي الإنجليزية

## CSS للدعم RTL:

```css
/* For RTL support */
[dir="rtl"] .sidebar {
    right: 0;
    left: auto;
}

[dir="rtl"] .main-content {
    margin-right: 260px;
    margin-left: 0;
}

[dir="ltr"] .sidebar {
    left: 0;
    right: auto;
}

[dir="ltr"] .main-content {
    margin-left: 260px;
    margin-right: 0;
}
```
