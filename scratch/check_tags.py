import re
import sys

def check_tags(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Remove script and style sections
    content = re.sub(r'<script.*?>.*?</script>', '', content, flags=re.DOTALL)
    content = re.sub(r'<style.*?>.*?</style>', '', content, flags=re.DOTALL)
    
    # Find all tags, including self-closing indicators
    tags = re.findall(r'<(/?[a-zA-Z0-9:-]+)(.*?)>', content)
    
    stack = []
    for tag_name_raw, attributes in tags:
        is_closing = tag_name_raw.startswith('/')
        tag_name = tag_name_raw.lstrip('/')
        is_self_closing = attributes.strip().endswith('/')
        
        if is_self_closing or tag_name.lower() in ['img', 'br', 'hr', 'input', 'link', 'meta', 'base', 'col', 'embed', 'area', 'param', 'source', 'track', 'wbr']:
            continue
        
        if is_closing:
            if not stack:
                print(f"Extra closing tag: </{tag_name}>")
            else:
                top = stack.pop()
                if top != tag_name:
                    print(f"Mismatched closing tag: </{tag_name}> (expected </{top}>)")
        else:
            stack.append(tag_name)
    
    for tag in reversed(stack):
        print(f"Unclosed tag: <{tag}>")

if __name__ == "__main__":
    if len(sys.argv) > 1:
        check_tags(sys.argv[1])
