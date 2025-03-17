from runtime import Args
from typings.md_to_docx.md_to_docx import Input, Output
from typing import TypedDict, Any, Optional
import requests

class Input(TypedDict):
    """输入类型定义"""
    content: str
    api_url: str
    format: str

def Mggo(content: str, api_url: str = 'https://pandoc.topvps.store', output_format: str = 'docx') -> dict:
    """
    主函数：处理markdown到docx的转换
    Args:
        content: markdown内容
        api_url: API服务器地址
        output_format: 输出格式，默认docx
    """
    print(f"Debug - Content length: {len(content)}")
    print(f"Debug - Using API URL: {api_url}")
    print(f"Debug - Output format: {output_format}")

    # 请求的 URL
    url = api_url

    # 准备请求的数据
    headers = {
        'Content-Type': 'application/x-www-form-urlencoded'
    }
    
    data = {
        'content': content,
        'format': output_format
    }

    try:
        # 发送POST请求
        response = requests.post(url, data=data, headers=headers)
        
        # 获取响应内容
        response_text = response.text
        
        try:
            response_json = response.json()
        except ValueError:
            response_json = {"raw_content": response_text}

        # 检查响应状态码
        if response.status_code != 200:
            return {
                "docx_url": "",
                "download_url": "",
                "error": f"请求失败，状态码: {response.status_code}",
                "server_response": response_json
            }

        # 获取下载链接
        download_url = response_json.get('download_url', '')
        full_url = url + download_url if download_url else ''
        
        return {
            "docx_url": full_url,
            "download_url": download_url,
            "error": None,
            "server_response": response_json
        }

    except Exception as e:
        import traceback
        return {
            "docx_url": "",
            "download_url": "",
            "error": f"请求过程中发生错误: {str(e)}",
            "server_response": {"error": traceback.format_exc()}
        }

def handler(args: Args[Input]) -> Output:
    """处理入口函数"""
    # 直接使用 args.input 而不是调用 get 方法
    content = args.input.content if args.input and hasattr(args.input, 'content') else '# 测试标题\n这是一个测试文档。'
    api_url = args.input.api_url if args.input and hasattr(args.input, 'api_url') else 'https://pandoc.topvps.store'
    output_format = args.input.format if args.input and hasattr(args.input, 'format') else 'docx'
    
    result = Mggo(
        content=content,
        api_url=api_url,
        output_format=output_format
    )
    
    return result