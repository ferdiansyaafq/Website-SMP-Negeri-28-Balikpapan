<?php
$page_title = "Kontak - SMP Negeri 28 Balikpapan";
include 'header.php';

$email_tujuan = htmlspecialchars($schoolContactEmail, ENT_QUOTES, 'UTF-8');
$email_subjek = rawurlencode('Pertanyaan dari Website Sekolah');
$email_isi = rawurlencode("isi pesan disini");

$gmail_link = "https://mail.google.com/mail/?view=cm&fs=1&to={$email_tujuan}&su={$email_subjek}&body={$email_isi}";
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: white;
        padding: 60px 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .page-header::before {
        content: '📞';
        position: absolute;
        right: 40px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 120px;
        opacity: 0.08;
    }
    .page-header h1 {
        font-size: 36px;
        font-weight: 800;
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
    }
    .page-header p {
        font-size: 16px;
        opacity: 0.9;
        position: relative;
        z-index: 1;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }

    .container {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 40px;
    }

    .quick-nav-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-top: 10px;
    }

    /* Layout Card di Tengah */
    .quick-nav-card {
        background: white;
        border-radius: 20px;
        padding: 40px 25px;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        border: 2px solid #f1f5f9;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        justify-content: center; 
        align-items: center;     
        text-align: center;      
        position: relative;
        overflow: hidden;
        height: 100%; 
    }

    .quick-nav-card::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 4px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .quick-nav-card:hover::after {
        opacity: 1;
    }

    .quick-nav-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.08);
        border-color: transparent;
    }

    .quick-nav-icon-wrapper {
        width: 70px;
        height: 70px;
        border-radius: 50%; 
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px auto;
        transition: transform 0.3s ease;
    }

    .quick-nav-card:hover .quick-nav-icon-wrapper {
        transform: scale(1.1);
    }

    .quick-nav-icon-wrapper svg {
        width: 35px;
        height: 35px;
    }

    .quick-nav-card h4 {
        font-size: 22px;
        color: #1e293b;
        margin-bottom: 8px;
        font-weight: 800;
    }

    .quick-nav-card p {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 24px;
        line-height: 1.6;
        word-break: break-word;
    }

    .quick-nav-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none;
        transition: gap 0.3s;
    }

    /* Warna Email */
    .card-email::after { background: linear-gradient(90deg, #ea4335, #fbbc05); }
    .card-email:hover { border-color: #fee2e2; }
    .card-email .quick-nav-icon-wrapper { background: #fef2f2; }
    .card-email .quick-nav-icon-wrapper svg { fill: #ea4335; }
    .card-email .quick-nav-link { color: #ea4335; }
    .card-email:hover .quick-nav-link { gap: 12px; }

    /* Warna WhatsApp */
    .card-wa::after { background: linear-gradient(90deg, #25D366, #128C7E); }
    .card-wa:hover { border-color: #dcfce7; }
    .card-wa .quick-nav-icon-wrapper { background: #f0fdf4; }
    .card-wa .quick-nav-icon-wrapper svg { fill: #25D366; }
    .card-wa .quick-nav-link { color: #25D366; }
    .card-wa:hover .quick-nav-link { gap: 12px; }

    @media (max-width: 768px) {
        .page-header { padding: 40px 20px; }
        .page-header h1 { font-size: 26px; }
        .page-header::before { font-size: 60px; right: 15px; }
        .container { padding: 0 20px; margin: 20px auto; }
        .quick-nav-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        .quick-nav-card {
            padding: 30px 20px;
        }
        .quick-nav-icon-wrapper {
            width: 60px;
            height: 60px;
        }
        .quick-nav-icon-wrapper svg {
            width: 30px;
            height: 30px;
        }
    }
</style>

<div class="page-header">
    <h1>📞 Kontak Sekolah</h1>
    <p>Hubungi SMP Negeri 28 Balikpapan melalui email atau WhatsApp.</p>
</div>

<div class="container">
    <div class="quick-nav-grid">
        
        <a href="<?= $gmail_link ?>" target="_blank" rel="noopener noreferrer" class="quick-nav-card card-email">
            <div>
                <div class="quick-nav-icon-wrapper">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                </div>
                <h4>Email</h4>
                <p><?= $email_tujuan ?></p>
            </div>
            <span class="quick-nav-link">Kirim Email →</span>
        </a>

        <!-- Kartu WhatsApp -->
        <a href="<?= htmlspecialchars($schoolWhatsappUrl, ENT_QUOTES, 'UTF-8') ?>" class="quick-nav-card card-wa" target="_blank" rel="noopener noreferrer">
            <div>
                <div class="quick-nav-icon-wrapper">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                </div>
                <h4>WhatsApp</h4>
                <p>Hubungi sekolah melalui WhatsApp.</p>
            </div>
            <span class="quick-nav-link">Buka WhatsApp →</span>
        </a>

    </div>
</div>

<?php include 'footer.php'; ?>