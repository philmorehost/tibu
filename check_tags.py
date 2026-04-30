import re
import sys

def check_balance(file_path):
    with open(file_path, 'r') as f:
        content = f.read()

    # Match opening tags (ignoring self-closing and PHP)
    tags = re.findall(r'<(div|aside|main|section|article|header|footer|nav|form)(?:\s+[^>]*?)?>|<\/(div|aside|main|section|article|header|footer|nav|form)>', content, re.IGNORECASE)

    stack = []
    line_num = 1
    for tag_match in re.finditer(r'<(div|aside|main|section|article|header|footer|nav|form)(?:\s+[^>]*?)?>|<\/(div|aside|main|section|article|header|footer|nav|form)>', content, re.IGNORECASE):
        tag_str = tag_match.group(0)
        open_tag = tag_match.group(1)
        close_tag = tag_match.group(2)

        line_num = content.count('\n', 0, tag_match.start()) + 1

        if open_tag:
            tag_name = open_tag.lower()
            stack.append((tag_name, line_num))
        else:
            tag_name = close_tag.lower()
            if not stack:
                print(f"Extra closing tag: </{tag_name}> at line {line_num}")
            else:
                last_name, last_line = stack.pop()
                if last_name != tag_name:
                    print(f"Mismatch: <{last_name}> from line {last_line} closed by </{tag_name}> at line {line_num}")

    if stack:
        print(f"Unclosed tags: {stack}")
    else:
        print("All tags balanced!")

if __name__ == "__main__":
    check_balance(sys.argv[1])
