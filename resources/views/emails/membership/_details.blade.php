<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border:1px solid #c6e8d4;background:#f8fdf9;border-radius:8px;margin:12px 0 20px;">
  @foreach ($rows as $label => $value)
    <tr>
      <td style="padding:8px 14px;font-weight:700;color:#0a5e3a;width:46%;vertical-align:top;">{{ $label }}</td>
      <td style="padding:8px 14px;color:#222;vertical-align:top;">{{ $value }}</td>
    </tr>
  @endforeach
</table>
