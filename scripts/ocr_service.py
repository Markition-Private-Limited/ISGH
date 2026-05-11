#!/usr/bin/env python3
"""
PaddleOCR microservice for Texas ID / Driver License field extraction.
Called by Laravel via: python ocr_service.py <image_path>
Outputs a single JSON object to stdout.
"""

import sys
import json
import re
import os


def _clean_value(s: str) -> str:
    """Strip leading field number labels like '1', '2', '8', '1.' etc."""
    return re.sub(r'^\d+[a-zA-Z]?\s*', '', s).strip()


def _parse_date(raw: str) -> str:
    parts = re.split(r'[/\-]', raw.strip())
    if len(parts) == 3:
        return f"{parts[0].zfill(2)}/{parts[1].zfill(2)}/{parts[2]}"
    return raw


def extract_fields(lines: list) -> dict:
    result = {
        "first_name":   "",
        "middle_name":  "",
        "last_name":    "",
        "dob":          "",
        "id_number":    "",
        "address":      "",
        "city":         "",
        "state":        "",
        "zip":          "",
    }

    full_text = " ".join(lines)

    # ── DOB — anywhere: "DOB 04/15/1944" or "3. DOB: 04/15/1944" ───────────
    m = re.search(r'DOB[:\s]*(\d{1,2}[/\-]\d{1,2}[/\-]\d{4})', full_text, re.IGNORECASE)
    if m:
        result["dob"] = _parse_date(m.group(1))

    # ── DL / ID number ────────────────────────────────────────────────────────
    # "4d. DL: 14679995" or "Id DL" then next line "12345678"
    m = re.search(r'(?:4[a-z]?\.?\s*)?DL[:\s]+([A-Z]?\d{6,8})', full_text, re.IGNORECASE)
    if m:
        result["id_number"] = m.group(1).strip()
    else:
        # DL label on one line, number on the next
        for i, line in enumerate(lines):
            if re.search(r'\bDL\b', line, re.IGNORECASE) and i + 1 < len(lines):
                nxt = lines[i + 1].strip()
                if re.match(r'^[A-Z]?\d{6,8}$', nxt):
                    result["id_number"] = nxt
                    break
        # Final fallback: any standalone 7-8 digit number
        if not result["id_number"]:
            for line in lines:
                m2 = re.match(r'^\s*([A-Z]?\d{7,8})\s*$', line.strip())
                if m2:
                    result["id_number"] = m2.group(1)
                    break

    NON_NAME = re.compile(
        r'DOB|DL|Iss|Exp|Class|Rest|End|Hgt|Sex|Eye|Wgt|Hair|DD|Texas|USA|'
        r'DRIVER|LICENSE|IDENTIFICATION|DONOR|OFFICIAL|DOCUMENT|PH\s?\d',
        re.IGNORECASE
    )

    # ── Last name — field "1": "1. KHAN" / "1KHAN" / line before field-2 line ─
    field2_idx = None
    for i, line in enumerate(lines):
        m = re.match(r'^\s*2\.?\s*([A-Z][A-Z\s\-\']+)$', line.strip(), re.IGNORECASE)
        if m:
            field2_idx = i
            break

    for line in lines:
        m = re.match(r'^\s*1\.?\s*([A-Z][A-Z\s\-\']+)$', line.strip(), re.IGNORECASE)
        if m:
            result["last_name"] = m.group(1).strip().title()
            break

    # If field-1 not found, use the all-caps line immediately before the field-2 line
    if not result["last_name"] and field2_idx and field2_idx > 0:
        candidate = lines[field2_idx - 1].strip()
        if re.match(r'^[A-Z][A-Z\s\-\']+$', candidate) and not NON_NAME.search(candidate):
            result["last_name"] = candidate.title()

    # ── First + middle — field "2": "2. MOHAMMAD HANEEF" or "2JANICE" ───────
    for line in lines:
        m = re.match(r'^\s*2\.?\s*([A-Z][A-Z\s\-\']+)$', line.strip(), re.IGNORECASE)
        if m:
            name_parts = m.group(1).strip().split()
            result["first_name"]  = name_parts[0].title() if name_parts else ""
            result["middle_name"] = " ".join(p.title() for p in name_parts[1:]) if len(name_parts) > 1 else ""
            break

    # ── Address — field "8": "8. 711 FM..." or merged "8120 OLD ST" ──────────
    street_idx = None
    for i, line in enumerate(lines):
        ls = line.strip()
        # Explicit "8." label
        m = re.match(r'^\s*8\.\s+(.+)$', ls, re.IGNORECASE)
        if m and re.search(r'\d', m.group(1)):
            result["address"] = m.group(1).strip().title()
            street_idx = i
            break
        # Field "8" merged: "8" + digits + space + letters, e.g. "8711 OLD MAIN ST"
        m2 = re.match(r'^8(\d+\s+[A-Z].+)$', ls, re.IGNORECASE)
        if m2 and not NON_NAME.search(ls):
            result["address"] = m2.group(1).strip().title()
            street_idx = i
            break
        # Plain street number (no label), including OCR-merged "2120OLD ST" → "2120 Old St"
        # Skip lines already consumed as field-2 (first name)
        already_used_as_name = result["first_name"] and ls.lstrip('0123456789. ').upper().startswith(result["first_name"].upper())
        if not already_used_as_name:
            m3 = re.match(r'^(\d{1,6})([A-Z].+)$', ls, re.IGNORECASE)
            if m3 and not result["address"] and not NON_NAME.search(ls):
                result["address"] = (m3.group(1) + " " + m3.group(2)).title()
                street_idx = i
            elif re.match(r'^\d{1,6}\s+[A-Z]', ls, re.IGNORECASE) and not result["address"]:
                if not NON_NAME.search(ls):
                    result["address"] = ls.title()
                    street_idx = i

    # ── City / State / ZIP — "HOUSTON TX 77034" or "ANYTOWN TX 12345-0000" ───
    csz_re = re.compile(r'^(.+?),?\s+([A-Z]{2})\s+(\d{5}(?:-\d{4})?)$', re.IGNORECASE)
    search_lines = lines if street_idx is None else lines[street_idx:]
    for line in search_lines:
        m = csz_re.match(line.strip())
        if m:
            city_candidate = m.group(1).strip()
            # Skip if city looks like a street (starts with digits)
            if not re.match(r'^\d', city_candidate):
                result["city"]  = city_candidate.title()
                result["state"] = m.group(2).upper()
                result["zip"]   = m.group(3)
                break

    # ── Fallback names: all-caps word blocks if field labels not found ────────
    if not result["last_name"] and not result["first_name"]:
        skip_kw = {"TEXAS","DRIVER","LICENSE","IDENTIFICATION","CLASS","NONE",
                   "RESTRICTIONS","ENDORSEMENTS","ISS","EXP","DONOR","OFFICIAL",
                   "DOCUMENT","SAMPLE","DRIVER LICENSE","USA"}
        candidates = []
        for line in lines:
            ls = line.strip()
            upper = ls.upper()
            if re.match(r'^[A-Z][A-Z\s\-\']+$', ls) and " " in ls and len(ls) > 3:
                if not any(upper.startswith(kw) for kw in skip_kw):
                    candidates.append(ls)
        # Field 1 = last name (first all-caps line), field 2 = first name (second)
        if len(candidates) >= 2:
            result["last_name"]   = candidates[0].strip().title()
            parts = candidates[1].strip().split()
            result["first_name"]  = parts[0].title()
            result["middle_name"] = " ".join(p.title() for p in parts[1:]) if len(parts) > 1 else ""
        elif len(candidates) == 1:
            parts = candidates[0].strip().split()
            result["first_name"] = parts[0].title()
            result["last_name"]  = parts[-1].title() if len(parts) > 1 else ""

    return result


def run_paddleocr(image_path: str) -> list:
    from paddleocr import PaddleOCR  # type: ignore
    from PIL import Image  # type: ignore

    # Convert webp / unsupported formats to JPEG so PaddleOCR can read them
    converted = None
    if image_path.lower().endswith('.webp') or not image_path.lower().endswith(('.jpg', '.jpeg', '.png', '.bmp')):
        converted = image_path + '.jpg'
        img = Image.open(image_path).convert('RGB')
        img.save(converted, 'JPEG')
        image_path = converted

    try:
        ocr = PaddleOCR(use_angle_cls=True, lang="en", show_log=False)
        result = ocr.ocr(image_path, cls=True)
        lines = []
        if result and result[0]:
            for item in result[0]:
                if item and len(item) >= 2:
                    text = item[1][0] if isinstance(item[1], (list, tuple)) else item[1]
                    lines.append(str(text))
        return lines
    finally:
        if converted and os.path.exists(converted):
            os.remove(converted)


def extract_card_fields(lines: list) -> dict:
    """Extract cardholder name from a credit/debit card image."""
    result = {"cardholder_name": ""}

    full = " ".join(lines)

    # Card number pattern — 16 digits in groups of 4 (with spaces, dashes, or dots)
    card_num_re = re.compile(r'\b(\d{4}[\s\-\.]\d{4}[\s\-\.]\d{4}[\s\-\.]\d{4})\b')

    # Find the line index of the card number so we can look nearby for the name
    card_line_idx = None
    for i, line in enumerate(lines):
        if card_num_re.search(line):
            card_line_idx = i
            break

    # Cardholder name:
    # - On Visa/MC/Amex it appears BELOW the card number
    # - Usually all-caps, 2–4 words, no digits
    # - Skip known non-name tokens
    skip_tokens = re.compile(
        r'VISA|MASTERCARD|AMEX|AMERICAN\s+EXPRESS|DISCOVER|CREDIT|DEBIT|'
        r'BANK|VALID|THRU|GOOD|FROM|MEMBER|SINCE|PLATINUM|GOLD|CLASSIC|'
        r'STANDARD|WORLD|SIGNATURE|INFINITE|CONTACTLESS',
        re.IGNORECASE
    )
    # Match ALL-CAPS names ("SHAHEER ZAEEM") or Title-Case names ("Shaheer Zaeem")
    name_line_re = re.compile(r'^[A-Za-z][A-Za-z\s\.\-]+$')

    single_word_re = re.compile(r'^[A-Z]{2,}$')

    def _pick_name(search_lines):
        """
        Return the first plausible cardholder name from a list of lines.
        Handles two layouts:
          (a) Single line: "SHAHEER ZAEEM"
          (b) Two consecutive lines: "SHAHEER" then "ZAEEM"
        """
        i = 0
        while i < len(search_lines):
            ls = search_lines[i].strip()
            # Layout (a): multi-word line, all caps or title case
            if " " in ls and name_line_re.match(ls) and not skip_tokens.search(ls) and ls != ls.lower():
                return ls
            # Layout (b): single ALL-CAPS word — try to merge with the next word
            if single_word_re.match(ls) and not skip_tokens.search(ls):
                j = i + 1
                while j < len(search_lines):
                    nxt = search_lines[j].strip()
                    if single_word_re.match(nxt) and not skip_tokens.search(nxt):
                        j += 1
                    else:
                        break
                if j > i + 1:  # found at least 2 consecutive all-caps words
                    return " ".join(search_lines[k].strip() for k in range(i, j))
            i += 1
        return ""

    # Search lines after card number first, then before it
    search_from = (card_line_idx + 1) if card_line_idx is not None else 0
    name = _pick_name(lines[search_from:])
    if not name and card_line_idx is not None:
        name = _pick_name(lines[:card_line_idx])
    elif not name and card_line_idx is None:
        name = _pick_name(lines)

    if name:
        result["cardholder_name"] = name.title()

    return result


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No image path provided"}))
        sys.exit(1)

    # Optional --mode flag: "id" (default) or "card"
    mode = "id"
    args = sys.argv[1:]
    if "--mode" in args:
        idx = args.index("--mode")
        if idx + 1 < len(args):
            mode = args[idx + 1]
            args = [a for a in args if a not in ("--mode", mode)]

    image_path = args[0] if args else ""
    if not image_path or not os.path.isfile(image_path):
        print(json.dumps({"error": f"File not found: {image_path}"}))
        sys.exit(1)

    try:
        lines = run_paddleocr(image_path)
    except ImportError:
        print(json.dumps({"error": "paddleocr not installed. Run: pip install paddleocr paddlepaddle"}))
        sys.exit(1)
    except Exception as e:
        import traceback
        print(json.dumps({"error": str(e), "trace": traceback.format_exc()}))
        sys.exit(1)

    if mode == "card":
        fields = extract_card_fields(lines)
    else:
        fields = extract_fields(lines)

    # Include raw OCR lines in output for debugging (logged by Laravel, not shown to user)
    fields["_ocr_lines"] = lines
    print(json.dumps(fields))


if __name__ == "__main__":
    main()
