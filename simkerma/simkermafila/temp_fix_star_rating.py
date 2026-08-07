from pathlib import Path
import re

path = Path('resources/views/livewire/landing/case-studies.blade.php')
text = path.read_text(encoding='utf-8')

pattern = re.compile(
    r'<div class="rating-group">\s*@for\(\$i = 1; \$i <= 5; \$i\+\+\)\s*'
    r'<label class="star-label">\s*'
    r'<input type="radio" name="(?P<name>[^"]+)" value="\{\{ \$i \}\}" wire:model="(?P<model>[^"]+)" /\>\s*'
    r'<span class="star [^"]+">★</span>\s*'
    r'</label>\s*'
    r'@endfor\s*'
    r'</div>',
    re.S,
)


def replace_group(match):
    name = match.group('name')
    model = match.group('model')
    return (
        '<div class="rating-group">\n'
        '                            @for($i = 5; $i >= 1; $i--)\n'
        f'                                <input class="rating-input" type="radio" id="{name}-{{{{ $i }}}}" name="{name}" value="{{{{ $i }}}}" wire:model="{model}" />\n'
        f'                                <label class="star-label" for="{name}-{{{{ $i }}}}">★</label>\n'
        '                            @endfor\n'
        '                        </div>'
    )

new_text, count = pattern.subn(replace_group, text)
print(f'Replaced {count} rating group blocks')

new_text = new_text.replace(
    '.rating-group {\n            display: flex;\n            justify-content: flex-start;\n            gap: 0.5rem;\n            align-items: center;\n            margin-top: 0.5rem;\n        }',
    '.rating-group {\n            display: flex;\n            flex-direction: row-reverse;\n            justify-content: flex-start;\n            gap: 0.5rem;\n            align-items: center;\n            margin-top: 0.5rem;\n        }'
)

new_text = new_text.replace(
    '.rating-group input {\n            position: absolute;\n            opacity: 0;\n            width: 100%;\n            height: 100%;\n            top: 0;\n            left: 0;\n            cursor: pointer;\n        }',
    '.rating-input {\n            display: none;\n        }'
)

new_text = new_text.replace(
    '.star-label {\n            display: inline-flex;\n            align-items: center;\n            justify-content: center;\n            cursor: pointer;\n            position: relative;\n            width: 2.6rem;\n            height: 2.6rem;\n        }',
    '.star-label {\n            display: inline-flex;\n            align-items: center;\n            justify-content: center;\n            cursor: pointer;\n            position: relative;\n            width: 2.6rem;\n            height: 2.6rem;\n            font-size: 2rem;\n            color: rgba(148, 163, 184, 0.9);\n            transition: color 0.2s ease, transform 0.2s ease;\n        }'
)

# Remove old .star block if still present
old_star_block = (
    '.star {\n            cursor: pointer;\n            font-size: 2rem;\n            color: rgba(148, 163, 184, 0.9);\n            transition: color 0.2s ease, transform 0.2s ease;\n        }\n\n'
    '        .star-label input:checked + .star,\n'
    '        .star.selected {\n'
    '            color: #f59e0b;\n'
    '            transform: translateY(-2px);\n'
    '        }'
)
new_text = new_text.replace(old_star_block, '')

if 'input:checked ~ .star-label' not in new_text:
    insert_pos = new_text.find('.star-label {')
    if insert_pos != -1:
        insert_end = new_text.find('}', insert_pos) + 1
        new_text = (
            new_text[:insert_end]
            + '\n\n        .rating-input {\n            display: none;\n        }\n\n        .star-label:hover,\n        .star-label:hover ~ .star-label,\n        .rating-input:checked ~ .star-label {\n            color: #f59e0b;\n            transform: translateY(-2px);\n        }\n'
            + new_text[insert_end:]
        )

path.write_text(new_text, encoding='utf-8')
