# KAIH - Frontend Structure Guide

## Struktur Frontend yang Baru

Sistem telah diupdate dengan header dan footer yang modern dan responsive. Berikut adalah panduan penggunaannya.

---

## Folder Structure

```
assets/
├── css/
│   ├── style.css      # Styling umum dan utility classes
│   ├── header.css     # Styling untuk header dan navigation
│   ├── footer.css     # Styling untuk footer
│   └── home.css       # Styling khusus untuk halaman home
├── js/
│   └── header.js      # JavaScript untuk hamburger menu dan dropdown
└── img/               # Folder untuk menyimpan gambar/logo
```

---

## Cara Menggunakan Header dan Footer

### 1. Halaman Dasar (Basic Page)

Untuk membuat halaman baru yang menggunakan header dan footer, gunakan struktur berikut:

```php
<?php
/**
 * Nama Halaman
 */

$page_title = 'Judul Halaman - KAIH';
include 'includes/header.php';
?>

    <!-- Konten halaman di sini -->
    <section class="container">
        <h2>Konten Halaman</h2>
        <p>Isi konten Anda di sini...</p>
    </section>

<?php include 'includes/footer.php'; ?>
```

### 2. Header Sections

Di dalam `includes/header.php` disediakan:

- **Logo**: Placeholder icon yang bisa diubah dengan logo sebenarnya
- **Navigation Menu**: Menu utama dengan dropdown untuk Admin
- **Search Box**: Fitur pencarian (bisa dikembangkan)
- **Responsive Design**: Mobile-friendly dengan hamburger menu

### 3. Footer Sections

Di dalam `includes/footer.php` disediakan:

- **About Section**: Informasi tentang KAIH
- **Quick Links**: Link-link penting
- **Contact Info**: Informasi kontak
- **Social Media**: Link ke media sosial
- **Copyright**: Informasi copyright

---

## Fitur Utama

### Header Features:
- ✅ Logo dengan icon placeholder (kosong untuk sementara)
- ✅ Navigation menu dengan submenu dropdown
- ✅ Search box terintegrasi
- ✅ Hamburger menu untuk mobile
- ✅ Active page indicator
- ✅ Fully responsive

### Footer Features:
- ✅ About section
- ✅ Quick navigation links
- ✅ Contact information
- ✅ Social media links
- ✅ Fully responsive

---

## CSS Classes yang Tersedia

### Buttons
```html
<a href="#" class="btn btn-primary">Primary Button</a>
<a href="#" class="btn btn-secondary">Secondary Button</a>
<a href="#" class="btn btn-success">Success Button</a>
<a href="#" class="btn btn-danger">Danger Button</a>
<a href="#" class="btn btn-warning">Warning Button</a>

<!-- Sizes -->
<a href="#" class="btn btn-primary btn-sm">Small</a>
<a href="#" class="btn btn-primary btn-lg">Large</a>
```

### Alerts
```html
<div class="alert alert-success">Success message</div>
<div class="alert alert-danger">Error message</div>
<div class="alert alert-warning">Warning message</div>
<div class="alert alert-info">Info message</div>
```

### Cards
```html
<div class="card">
    <div class="card-header">
        <h3>Card Title</h3>
    </div>
    <div class="card-body">
        <p>Card content here</p>
    </div>
    <div class="card-footer">
        <button class="btn btn-primary">Action</button>
    </div>
</div>
```

### Forms
```html
<div class="form-group">
    <label class="form-label">Label</label>
    <input type="text" class="form-control" placeholder="Placeholder">
</div>

<div class="form-group">
    <label class="form-label">Textarea</label>
    <textarea class="form-control"></textarea>
</div>
```

### Tables
```html
<table class="table">
    <thead>
        <tr>
            <th>Header 1</th>
            <th>Header 2</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Data 1</td>
            <td>Data 2</td>
        </tr>
    </tbody>
</table>
```

### Badges
```html
<span class="badge">Default</span>
<span class="badge badge-primary">Primary</span>
<span class="badge badge-success">Success</span>
<span class="badge badge-danger">Danger</span>
<span class="badge badge-warning">Warning</span>
```

---

## Customization

### Logo
Untuk mengganti logo placeholder dengan image/logo sebenarnya:

Edit file `includes/header.php` dan ubah bagian:

```php
<div class="logo-placeholder">
    <i class="fas fa-graduation-cap"></i>
</div>
```

Dengan:

```php
<img src="path/to/your/logo.png" alt="Logo" class="logo-img" width="45" height="45">
```

Kemudian tambahkan CSS di `assets/css/header.css`:

```css
.logo-img {
    border-radius: 50%;
}
```

### Colors
Untuk mengganti warna, edit CSS variables di `assets/css/header.css` bagian `:root`:

```css
:root {
    --primary-color: #2563eb;      /* Warna utama */
    --secondary-color: #1e40af;    /* Warna sekunder */
    --accent-color: #f59e0b;       /* Warna accent */
    --text-dark: #1f2937;          /* Warna teks gelap */
    --text-light: #6b7280;         /* Warna teks ringan */
    --bg-light: #f9fafb;           /* Background ringan */
    --bg-white: #ffffff;           /* Background putih */
    --border-color: #e5e7eb;       /* Warna border */
}
```

### Menu Items
Untuk menambah/mengurangi menu items, edit file `includes/header.php` bagian `nav-menu`.

---

## JavaScript Functions

### Toast Notification
```javascript
showToast('Message content', 'success');
// Types: 'success', 'error', 'warning', 'info'
```

### Example Usage:
```javascript
showToast('Data berhasil disimpan!', 'success');
showToast('Terjadi kesalahan!', 'error');
```

---

## File Files

- ✅ **includes/header.php** - Header component
- ✅ **includes/footer.php** - Footer component
- ✅ **assets/css/style.css** - General styling
- ✅ **assets/css/header.css** - Header styling
- ✅ **assets/css/footer.css** - Footer styling
- ✅ **assets/css/home.css** - Home page styling
- ✅ **assets/js/header.js** - Header JavaScript
- ✅ **home.php** - Example home page

---

## Responsive Breakpoints

- **Desktop**: 1200px and above
- **Tablet**: 768px to 1199px
- **Mobile**: Below 768px
- **Small Mobile**: Below 480px

---

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers

---

## Notes

1. **Logo**: Saat ini menggunakan icon placeholder. Ganti dengan logo sebenarnya
2. **Search Box**: Implementasi pencarian bisa dikembangkan lebih lanjut
3. **Social Media Links**: Update dengan link sebenarnya di footer
4. **Kontak Info**: Update nomor kontak, email, dan alamat sebenarnya
5. **Colors**: Bisa di-customize sesuai brand identity

---

## Next Steps

1. Ganti logo placeholder dengan logo sebenarnya
2. Update informasi kontak di footer
3. Tambahkan halaman-halaman yang diperlukan (login, portal, dll)
4. Customize warna sesuai brand
5. Implementasi fitur pencarian
6. Testing di berbagai device

---

**Last Updated**: March 16, 2026
**Version**: 1.0
