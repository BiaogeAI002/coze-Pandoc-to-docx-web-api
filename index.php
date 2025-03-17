<?php
// 开启错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 记录错误到文件
function logError($message) {
    $logFile = __DIR__ . '/error.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// 检查pandoc是否可用
function isPandocAvailable() {
    try {
        exec('pandoc --version 2>&1', $output, $returnCode);
        logError("Pandoc check result: " . implode("\n", $output) . ", return code: $returnCode");
        return $returnCode === 0 && !empty($output) && strpos($output[0], 'pandoc') !== false;
    } catch (Exception $e) {
        logError("Error checking pandoc: " . $e->getMessage());
        return false;
    }
}

try {
    // 如果收到POST请求
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 首先检查pandoc是否可用
        if (!isPandocAvailable()) {
            throw new Exception('Pandoc未安装或不可用。请先安装Pandoc: https://pandoc.org/installing.html');
        }

        // 获取POST数据，支持form-data和raw json两种格式
        $content = '';
        $rawInput = file_get_contents('php://input');
        logError("Raw input received: " . substr($rawInput, 0, 200));
        
        if (isset($_POST['content'])) {
            // 如果是form-data格式
            $content = $_POST['content'];
            logError("Content received from POST data");
        } else {
            // 如果是raw json格式
            $input = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON解析错误: ' . json_last_error_msg());
            }
            $content = isset($input['content']) ? $input['content'] : '';
            logError("Content received from JSON data");
        }
        
        if (empty($content)) {
            throw new Exception('内容不能为空');
        }

        // 创建临时目录（如果不存在）
        $tempDir = sys_get_temp_dir() . '/md2docx';
        if (!file_exists($tempDir)) {
            if (!mkdir($tempDir, 0700, true)) {
                throw new Exception("无法创建临时目录: $tempDir");
            }
        }

        // 生成唯一文件名
        $uuid = uniqid();
        $mdPath = $tempDir . "/{$uuid}.md";
        $docxPath = $tempDir . "/{$uuid}.docx";

        // 确保内容使用UTF-8编码并保留换行符
        $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        
        // 将\n字面文本转换为实际换行符
        $content = str_replace('\\n', "\n", $content);
        
        // 标准化换行符为\n
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        
        // 保存Markdown内容到临时文件，使用UTF-8编码
        if (file_put_contents($mdPath, $content, LOCK_EX) === false) {
            throw new Exception("无法写入临时文件: $mdPath");
        }
        logError("Markdown content saved to: $mdPath");

        // 调用Pandoc转换，添加更多参数以确保正确处理
        $command = sprintf(
            'pandoc -f markdown-raw_tex -t docx --top-level-division=chapter --wrap=none --reference-doc=template.docx "%s" -o "%s" 2>&1',
            $mdPath,
            $docxPath
        );
        logError("Executing command: $command");
        
        // 记录转换前的内容
        logError("Content before conversion:\n" . $content);
        
        exec($command, $output, $returnCode);
        logError("Pandoc output: " . implode("\n", $output) . ", return code: $returnCode");
        
        // 检查是否成功
        if ($returnCode === 0 && file_exists($docxPath) && filesize($docxPath) > 0) {
            // 生成安全下载链接
            $downloadUrl = "/download.php?token={$uuid}";
            $response = [
                'success' => true,
                'download_url' => $downloadUrl
            ];
        } else {
            throw new Exception('转换失败，错误信息：' . (!empty($output) ? implode("\n", $output) : '未知错误'));
        }

        // 清理临时MD文件
        @unlink($mdPath);
        
        echo json_encode($response);
        exit;
    } else {
        throw new Exception('请使用POST方法请求');
    }
} catch (Exception $e) {
    logError("Error occurred: " . $e->getMessage());
    $mdContent = file_exists($mdPath) ? file_get_contents($mdPath) : 'File not found';
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'command' => isset($command) ? $command : null,
            'output' => isset($output) ? $output : null,
            'return_code' => isset($returnCode) ? $returnCode : null,
            'content_length' => isset($content) ? strlen($content) : 0,
            'temp_dir' => isset($tempDir) ? $tempDir : null,
            'md_path' => isset($mdPath) ? $mdPath : null,
            'content_sample' => isset($content) ? substr($content, 0, 200) : null,
            'original_content' => $mdContent
        ]
    ]);
}
