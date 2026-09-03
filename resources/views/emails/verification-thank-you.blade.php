<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ISGH Membership Verification</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      background: #f4f6f4;
      font-family: 'Helvetica Neue', Arial, sans-serif;
      color: #222;
      padding: 40px 20px;
    }

    .wrapper {
      max-width: 560px;
      margin: 0 auto;
      background: #ffffff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0,0,0,0.07);
    }

    .header {
      background: linear-gradient(135deg, #0a5e3a 0%, #12a060 100%);
      padding: 36px 40px 28px;
      text-align: center;
    }

    .header img {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      border: 2px solid rgba(255,255,255,0.4);
      margin-bottom: 14px;
    }

    .header h1 {
      font-size: 20px;
      font-weight: 700;
      color: #ffffff;
      letter-spacing: -0.2px;
      line-height: 1.3;
    }

    .header p {
      font-size: 13px;
      color: rgba(255,255,255,0.75);
      margin-top: 6px;
    }

    .body {
      padding: 36px 40px 32px;
    }

    .greeting {
      font-size: 15px;
      color: #333;
      line-height: 1.75;
      margin-bottom: 24px;
    }

    .section-title {
      font-size: 13px;
      font-weight: 700;
      color: #0a5e3a;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: 12px;
    }

    .steps {
      background: #f8fdf9;
      border: 1px solid #c6e8d4;
      border-radius: 10px;
      padding: 20px 24px;
      margin-bottom: 24px;
    }

    .step {
      display: flex;
      gap: 12px;
      align-items: flex-start;
      margin-bottom: 14px;
    }

    .step:last-child { margin-bottom: 0; }

    .step-dot {
      width: 8px;
      height: 8px;
      background: #12a060;
      border-radius: 50%;
      margin-top: 6px;
      flex-shrink: 0;
    }

    .step p {
      font-size: 14px;
      color: #444;
      line-height: 1.6;
    }

    .step p strong { color: #222; }

    .contact-note {
      font-size: 14px;
      color: #555;
      line-height: 1.75;
      margin-bottom: 24px;
    }

    .contact-note a {
      color: #0a5e3a;
      text-decoration: none;
      font-weight: 600;
    }

    .sign-off {
      font-size: 14px;
      color: #333;
      line-height: 1.75;
    }

    .footer {
      background: #f0f7f3;
      border-top: 1px solid #d4eadc;
      padding: 20px 40px;
      text-align: center;
    }

    .footer p {
      font-size: 12px;
      color: #7a9e8a;
      line-height: 1.7;
    }

    .footer a {
      color: #0a5e3a;
      text-decoration: none;
    }

    @media only screen and (max-width: 600px) {
      .body, .footer { padding-left: 24px; padding-right: 24px; }
      .header { padding-left: 24px; padding-right: 24px; }
    }
  </style>
</head>
<body>
  <div class="wrapper">

    <div class="header">
      <h1>ISGH Membership Verification</h1>
      <p>Islamic Society of Greater Houston</p>
    </div>

    <div class="body">

      <p class="greeting">
        Dear {{ $firstName }},<br><br>
        Jazakallah khair for submitting your information for membership verification.<br><br>
        We have successfully received your details and document upload. Our membership team is currently reviewing your submission to update your profile and confirm your online voting eligibility.
      </p>

      <p class="section-title">What happens next?</p>

      <div class="steps">
        <div class="step">
          <div class="step-dot"></div>
          <p><strong>Processing Time:</strong> Our team will process your information within 3 to 5 business days.</p>
        </div>
        <div class="step">
          <div class="step-dot"></div>
          <p><strong>Confirmation:</strong> Once your details are verified, you will receive a follow-up email confirming your updated membership status and voting eligibility.</p>
        </div>
      </div>

      <p class="contact-note">
        If you have any questions or need to make further updates in the meantime, please feel free to reply to this email or reach out to us at
        <a href="mailto:membership.verify@isgh.org">membership.verify@isgh.org</a>.
      </p>

      <p class="sign-off">
        Thank you for your patience and for your continued support of our community.<br><br>
        Warm regards,<br><br>
        <strong>ISGH Membership Team</strong><br>
        Islamic Society of Greater Houston<br>
        <a href="mailto:membership.verify@isgh.org">membership.verify@isgh.org</a> | (713) 524-6615 ext 105/108
      </p>

    </div>

    <div class="footer">
      <p>
        © {{ date('Y') }} Islamic Society of Greater Houston. All rights reserved.<br>
        3110 Eastside St, Houston, TX 77098
      </p>
    </div>

  </div>
</body>
</html>
