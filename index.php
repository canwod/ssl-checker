<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSL Kontrol Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF6B35;
            --secondary-color: #4ECDC4;
            --background-color: #ffffff;
        }
        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .domain-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }
        .domain-card:hover {
            transform: translateY(-5px);
        }
        .ssl-status {
            font-weight: bold;
        }
        .ssl-valid {
            color: #28a745;
        }
        .ssl-expired {
            color: #dc3545;
        }
        .ssl-warning {
            color: #ffc107;
        }
        .export-buttons {
            margin-bottom: 1rem;
        }
        .domain-form-container {
            margin-bottom: 2rem;
            width: 100%;
        }
        .domain-input-group {
            display: flex;
            gap: 10px;
        }
        .domain-input {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-shield-alt"></i> SSL Kontrol Paneli</h1>
            <p class="lead">Domain SSL durumlarını takip edin</p>
        </div>
    </div>

    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card domain-form-container">
                    <div class="card-body">
                        <h5 class="card-title">Yeni Domain Ekle</h5>
                        <form id="domainForm">
                            <div class="domain-input-group">
                                <input type="text" class="form-control domain-input" id="domainInput" placeholder="Domain adresini girin (örn: example.com)" required>
                                <button type="submit" class="btn btn-primary">Ekle</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Domain Listesi</h5>
                            <div class="export-buttons">
                                <a href="export.php?type=pdf" class="btn btn-danger">
                                    <i class="fas fa-file-pdf"></i> PDF İndir
                                </a>
                                <a href="export.php?type=excel" class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Excel İndir
                                </a>
                            </div>
                        </div>
                        <div id="domainList">
                            <!-- Domain listesi buraya dinamik olarak eklenecek -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Sayfa yüklendiğinde domainleri getir
            loadDomains();

            // Yeni domain ekleme formu
            $('#domainForm').on('submit', function(e) {
                e.preventDefault();
                const domain = $('#domainInput').val();
                
                $.ajax({
                    url: 'add_domain.php',
                    method: 'POST',
                    data: { domain: domain },
                    success: function(response) {
                        if(response.success) {
                            $('#domainInput').val('');
                            loadDomains();
                        } else {
                            alert('Hata: ' + response.message);
                        }
                    }
                });
            });

            // Domain silme işlemi
            $(document).on('click', '.delete-domain', function() {
                if (confirm('Bu domaini silmek istediğinizden emin misiniz?')) {
                    const id = $(this).data('id');
                    
                    $.ajax({
                        url: 'delete_domain.php',
                        method: 'POST',
                        data: { id: id },
                        success: function(response) {
                            if(response.success) {
                                loadDomains();
                            } else {
                                alert('Hata: ' + response.message);
                            }
                        }
                    });
                }
            });

            // Her 5 dakikada bir SSL durumlarını güncelle
            setInterval(loadDomains, 300000);
        });

        function loadDomains() {
            $.ajax({
                url: 'get_domains.php',
                method: 'GET',
                success: function(response) {
                    $('#domainList').html(response);
                }
            });
        }
    </script>
</body>
</html> 