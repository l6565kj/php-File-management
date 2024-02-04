<?php

$localIP = getLocalIP();
//echo "本机局域网IP地址是：$localIP";

function getLocalIP() {
    // 获取服务器变量中的主机名
    $hostName = gethostname();
    
    // 获取主机名对应的IP地址列表
    $ipList = gethostbynamel($hostName);
    
    // 从IP地址列表中筛选出本机局域网IP地址
    foreach ($ipList as $ip) {
        // 通常，本机局域网IP地址的前几位是192.168
        if (strpos($ip, '192.168.') === 0) {
            return $ip;
        }
    }

    // 如果没有找到符合条件的IP地址，返回空字符串或其他适当的值
    return '';
}

$directory = './';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['file'])) {
        $uploadedFiles = $_FILES['file'];

        foreach ($uploadedFiles['error'] as $key => $error) {
            if ($error === UPLOAD_ERR_OK) {
                $originalFileName = $uploadedFiles['name'][$key];
                $fileName = $directory . str_replace(' ', '-', $originalFileName); // Replace spaces with dashes
                move_uploaded_file($uploadedFiles['tmp_name'][$key], $fileName);
            }
        }
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        exit('No file uploaded or file too large.');
    }
    exit; // Exit here to prevent rendering the rest of the HTML
}


if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['file'])) {
    $fileToDelete = $_GET['file'];
    $filePathToDelete = $directory . $fileToDelete;

    if (file_exists($filePathToDelete) && $fileToDelete !== 'index.php') {
        unlink($filePathToDelete);
    }
}

function formatBytes($bytes) {
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = max(0, min(count($sizes) - 1, (int)floor(log($bytes) / log($k))));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

$files = array_diff(scandir($directory), array('..', '.', 'index.php'));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>六五文件资源管理器</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 20px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        h1 {
            margin: 0;
            color: #333;
        }
        


        ul {
            list-style: none;
            padding: 0;
        }

        li {
            background-color: #fff;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        a {
            text-decoration: none;
            color: #3498db;
            margin-left: 10px;
        }

        form {
            margin-top: 20px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        label {
            margin-bottom: 10px;
        }

        progress {
            width: 100%;
            margin-bottom: 10px;
            display: none;
        }

        p {
            margin-bottom: 10px;
        }

        button {
            background-color: #323d50;
            color: #fff;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="file"] {
            margin-bottom: 10px;
        }

        .file-actions {
            display: flex;
            align-items: center;
        }

        .file-actions a {
            margin-left: 10px;
            margin-right: 10px;
        }

        .file-info {
            margin-top: 10px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>

    <header>
        <h2>六五文件资源管理器: <?php echo $localIP; ?></h2>
        <button onclick="location.reload()">刷新</button>
    </header>


    <form id="upload-form" action="" method="post" enctype="multipart/form-data">
        <label for="file">选择文件上传：</label>
        <input type="file" name="file[]" id="file" required multiple>
        <progress id="upload-progress" value="0" max="100"></progress>
        <p id="upload-speed"></p>
        
        
        <!--<button type="button" onclick="uploadFile()">上传</button>-->
        <button type="button" onclick="uploadFile()" style="width: 100%; color: white; background-color: #323d50;">上传</button>
    </form>



    <ul>
        <?php foreach ($files as $file): ?>
            <?php if ($file !== 'index.php'): ?>
                <?php
                    $filePath = $directory . $file;
                    $lastModified = date('Y-m-d H:i:s', filemtime($filePath));
                    $fileSize = formatBytes(filesize($filePath));
                ?>
                <li>
                    <div>
                        <?php echo $file; ?>
                    </div>
                    <div class="file-info">
                        <span>最后修改时间: <?php echo $lastModified; ?></span>
                        <span>文件大小: <?php echo $fileSize; ?></span>
                    </div>
                    <div class="file-actions">
                        <a href="<?php echo $directory . urlencode($file); ?>" download title="下载">&#x1F4E5;</a>
                        <a href="?action=delete&file=<?php echo urlencode($file); ?>" onclick="return confirm('确定删除吗？')" title="删除">&#x1F5D1;</a>
                    </div>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>


    <script>
        function uploadFile() {
            const form = document.getElementById('upload-form');
            const progressBar = document.getElementById('upload-progress');
            const speedNode = document.getElementById('upload-speed');
            const fileInput = document.getElementById('file');

            progressBar.style.display = 'block';

            const xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    progressBar.value = percentComplete;
                    const speed = e.loaded / ((e.timeStamp - startTime) / 1000);
                    speedNode.textContent = `传输速度: ${formatBytes(speed)}/s`;
                }
            });

            xhr.addEventListener('loadstart', (e) => {
                startTime = e.timeStamp;
            });

            xhr.addEventListener('load', () => {
                progressBar.style.display = 'none';
                speedNode.textContent = '';
                fileInput.value = '';
                location.reload(); // 刷新页面
            });

            xhr.open('POST', form.action, true);
            const formData = new FormData(form);
            xhr.send(formData);
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.max(0, Math.min(sizes.length - 1, Math.floor(Math.log(bytes) / Math.log(k))));
            return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
        }
    </script>

</body>
</html>
