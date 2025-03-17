# Markdown转Word文档转换器

这是一个基于PHP和Pandoc的Markdown转Word文档(DOCX)转换工具。它提供了一个简单的HTTP API接口，可以将Markdown格式的文本转换为Word文档格式。

## 功能特点

- 支持Markdown到DOCX的转换
- 支持form-data和JSON两种请求格式
- 自动处理UTF-8编码
- 提供文件下载链接
- 详细的错误日志记录
- 支持自定义Word模板

## 系统要求

- PHP 7.0+
- Pandoc 2.0+
- 启用PHP exec()函数
- 文件写入权限

## 安装步骤

1. 安装Pandoc
   ```bash
   # Debian/Ubuntu
   sudo apt-get install pandoc

   # CentOS/RHEL
   sudo yum install pandoc

   # macOS
   brew install pandoc
   ```

2. 部署PHP文件
   - 将`index.php`和`download.php`放置在Web服务器目录下
   - 确保Web服务器对临时目录有写入权限

3. 配置Word模板（可选）
   - 将你的Word模板文件重命名为`template.docx`
   - 放置在与`index.php`相同的目录下

## API使用说明

### 接口地址

POST /index.php

### 请求格式

#### 使用form-data
```
Content-Type: multipart/form-data

content=你的Markdown内容
```

#### 使用JSON
```
Content-Type: application/json

{
    "content": "你的Markdown内容"
}
```

### 响应格式

#### 成功响应
```json
{
    "success": true,
    "download_url": "/download.php?token=xxxxx"
}
```

#### 错误响应
```json
{
    "success": false,
    "message": "错误信息",
    "debug": {
        "error": "详细错误信息",
        "trace": "错误追踪"
    }
}
```

## 示例代码

### cURL示例
```bash
# 使用form-data
curl -X POST \
  -F "content=# 标题\n\n这是内容" \
  http://your-domain/index.php

# 使用JSON
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"content":"# 标题\n\n这是内容"}' \
  http://your-domain/index.php
```

### JavaScript示例
```javascript
// 使用fetch和JSON
fetch('http://your-domain/index.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        content: '# 标题\n\n这是内容'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        window.location.href = data.download_url;
    }
});
```

## 故障排除

1. 如果遇到转换失败，请检查：
   - Pandoc是否正确安装（运行`pandoc --version`验证）
   - PHP是否有执行权限
   - 临时目录是否有写入权限
   - error.log文件中的详细错误信息

2. 如果文件下载失败，请确认：
   - download.php是否有正确的文件读取权限
   - 临时文件是否存在
   - URL路径是否正确

## 注意事项

- 转换后的临时文件会自动清理
- 建议在生产环境中设置适当的访问控制
- 大文件转换可能需要调整PHP配置（如max_execution_time）

## 许可证

MIT License 
