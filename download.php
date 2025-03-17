<?php
if (isset($_GET['token'])) {
    $token = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['token']);
    $tempDir = sys_get_temp_dir() . '/md2docx';
    $docxPath = $tempDir . "/{$token}.docx";

    // 验证文件是否存在且可读
    if (file_exists($docxPath) && is_readable($docxPath)) {
        // 设置响应头
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="document.docx"');
        header('Content-Length: ' . filesize($docxPath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        // 输出文件内容
        readfile($docxPath);
        
        // 文件下载完成后删除
        @unlink($docxPath);
        exit;
    }
}

// 如果文件不存在或token无效，返回404
header('HTTP/1.0 404 Not Found');
echo '文件不存在或已过期';
