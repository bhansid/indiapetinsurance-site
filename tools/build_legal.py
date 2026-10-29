"""Builds the legal pages (privacy-policy/, terms/, refund-policy/).
Edit the details below, then run:  python3 tools/build_legal.py
"""
import os, re

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
COMPANY = "Scram Technologies Pvt. Ltd."
BRAND = "IndiaPetInsurance.com"
EMAIL = "support@indiapetinsurance.com"
ADDRESS = "Jammu, Jammu and Kashmir, India"
UPDATED = "30 October 2026"
KIT_PRICE = "₹1,000"

PAW = '<svg width="22" height="22" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><ellipse cx="12" cy="16" rx="5" ry="4.2"/><circle cx="5.5" cy="10" r="2.2"/><circle cx="9.5" cy="6" r="2.2"/><circle cx="14.5" cy="6" r="2.2"/><circle cx="18.5" cy="10" r="2.2"/></svg>'

def page(slug, title, meta, h1, intro, summary, sections):
    toc = "".join(f'<li><a href="#{sid}">{st}</a></li>' for sid, st, _ in sections)
    body = "".join(f'<h2 id="{sid}">{i+1}. {st}</h2>{html}' for i, (sid, st, html) in enumerate(sections))
    summ = "".join(f"<li>{x}</li>" for x in summary)
    return f'''<!doctype html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{title} | {BRAND}</title>
<meta name="description" content="{meta}">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://indiapetinsurance.com/{slug}/">
<meta name="theme-color" content="#2457F5">
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="icon" href="/assets/favicon-32.png" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="/assets/style.css?v=7">
</head>
<body style="padding-bottom:0">
<nav class="top" aria-label="Main">
  <div class="wrap">
    <a class="logo" href="/"><span class="mk">{PAW}</span><span>IndiaPetInsurance<small>.com</small></span></a>
    <div class="navlinks"><a href="/">Free ID card</a><a href="/insurance/">Insurance</a><a href="/account/">Log in</a></div>
    <a class="btn btn-blue btn-sm" href="/#make-id">Get free ID</a>
  </div>
</nav>
<header class="legal-hero"><div class="wrap" style="max-width:812px"><span class="eyebrow">Legal</span><h1>{h1}</h1><p>{intro}</p><p class="small" style="font-size:.9rem">Last updated: {UPDATED}</p></div></header>
<main class="wrap"><article class="legal">
<div class="sum"><h2>In short</h2><ul>{summ}</ul></div>
<nav class="toc" aria-label="Contents"><b>Contents</b><ol>{toc}</ol></nav>
{body}
</article></main>
<footer>
  <div class="wrap">
    <div class="foot">
      <div><a class="logo" href="/"><span class="mk">{PAW}</span><span>IndiaPetInsurance<small>.com</small></span></a><p style="margin-top:12px">A dog and cat protection and healthcare platform by {COMPANY}, {ADDRESS}.</p></div>
      <div><b>Owners</b><ul><li><a href="/">Free ID card</a></li><li><a href="/insurance/">Pet insurance</a></li><li><a href="/account/">Owner log in</a></li></ul></div>
      <div><b>Help</b><ul><li><a href="mailto:{EMAIL}">{EMAIL}</a></li><li><a href="/#faq">FAQs</a></li></ul></div>
    </div>
    <div class="legalbar"><span>© 2026 {COMPANY}</span><nav aria-label="Legal"><a href="/privacy-policy/">Privacy Policy</a><a href="/terms/">Terms of Use</a><a href="/refund-policy/">Returns &amp; Refunds</a><a href="mailto:{EMAIL}">{EMAIL}</a></nav></div>
  </div>
</footer>
</body>
</html>
'''

CONTACT = f'''<div class="contact"><p><b>Grievance Officer</b><br>{COMPANY}<br>{ADDRESS}<br>Email: <a href="mailto:{EMAIL}">{EMAIL}</a></p><p style="margin:0">We acknowledge complaints within 48 hours and aim to resolve them within 30 days.</p></div>'''

privacy = page("privacy-policy", "Privacy Policy",
  f"How {BRAND} collects, uses, shares and protects information about you and your dog or cat.",
  "Privacy Policy",
  f"This policy explains what {BRAND} collects when you make an ID for your dog or cat, order a kit or ask for an insurance quote, and what we do with it.",
  ["We collect what we need to make the ID, remind you about vet care, deliver orders and get insurance quotes.",
   "You choose what a stranger sees when they scan your QR tag. Medical records are never public.",
   "We store only the last 4 digits of your ID-proof number.",
   "We share details with an insurer or insurance intermediary only when you ask for a quote.",
   "We do not sell your data. You can ask us to correct or delete it at any time."],
  [
  ("who", "Who we are", f"<p>{BRAND} is run by {COMPANY} (\"we\", \"us\"), {ADDRESS}. We are the data fiduciary for the personal data described here under the Digital Personal Data Protection Act, 2023 and the Information Technology Act, 2000.</p>"),
  ("collect", "Information we collect", f"""
   <h3>About your dog or cat</h3><p>Name, species, breed, sex, neutering status, date of birth or age, weight, colour, identification marks, photo, municipal licence number, Kennel Club of India registration number, microchip number, vaccination and deworming dates, health records you upload, allergies, conditions and medicines.</p>
   <h3>About you</h3><p>Your name, mobile number, email address, postal address, an emergency contact's name and mobile number, and the password you set (stored only in encrypted, hashed form).</p>
   <h3>ID proof</h3><p>If you choose to add it: the type of ID (for example Aadhaar, PAN or driving licence), the <b>last 4 digits</b> of its number and the images you upload. We do not store the full ID number. For Aadhaar, please upload a masked copy where possible.</p>
   <h3>Your vet</h3><p>Your clinic's name and area, your doctor's name and mobile number, and the clinic owner's and reception's numbers if you add them. Please only add numbers you are allowed to share; we use them for vet reminders and emergencies.</p>
   <h3>Orders and insurance quotes</h3><p>Delivery details for kit orders. For quotes: the plan and cover amount you choose, your answers to health questions and any photos you upload for the insurer.</p>
   <h3>Collected automatically</h3><p>Your IP address and browser details in our server logs, how you reached us (for example the website or advert you came from), and how many times a QR profile is opened. We do not record who scanned a tag beyond the technical log entry.</p>"""),
  ("use", "How we use it", """<ul><li>To create and show your dog or cat's ID card, QR profile and health record.</li><li>To send vaccination, deworming and check-up reminders, and order updates, by WhatsApp, SMS or email.</li><li>To process, engrave and deliver kit orders, and handle returns.</li><li>To pass your quote request to a licensed insurer or insurance intermediary, when you ask for one.</li><li>To run your account, keep the service secure and prevent fraud.</li><li>To understand which pages and adverts bring people to us, so we can improve.</li><li>To meet legal, tax and accounting obligations.</li></ul><p>We rely on your consent, which you give when you tick the box before creating an ID, and on legitimate uses permitted by law. You can withdraw consent at any time; this will not affect anything we did before you withdrew it.</p>"""),
  ("qr", "Your public QR profile", "<p>Anyone who scans your tag or opens your ID link can see your dog or cat's name, breed, sex, colour, photo, identification marks and ID number. They also see only what you allow: your phone number, your locality or exact address, your vet's details, and allergies or notes. You can change these choices at any time in your account. Medical records are never shown publicly.</p>"),
  ("share", "Who we share it with", """<ul><li><b>Insurers and IRDAI-registered insurance intermediaries</b>, only when you request a quote, so they can price and issue a policy.</li><li><b>Your vet clinic</b>, for reminders and records, when you name a clinic.</li><li><b>Courier partners</b>: your name, mobile and address, to deliver orders.</li><li><b>Service providers</b> who host our website and data, or send messages for us, under confidentiality obligations.</li><li><b>Authorities</b>, when the law requires it.</li></ul><p>We do not sell or rent your personal data.</p>"""),
  ("cookies", "Cookies and local storage", "<p>We use one essential cookie to keep you logged in. Your browser also keeps your unfinished ID form on your own device so you don't lose it; you can clear it in your browser settings. We do not use advertising cookies at present. Our pages load fonts from Google Fonts and a code library from Cloudflare's cdnjs, which receive your IP address when your browser fetches those files.</p>"),
  ("security", "How we protect it", "<p>The site uses HTTPS. Passwords are hashed. Uploaded documents are stored outside the public website and can only be opened by you through your account or by authorised staff, and access to ID proofs is logged. No system is completely secure; if a breach affects you, we will tell you and the authorities as the law requires.</p>"),
  ("retention", "How long we keep it", "<p>We keep your account and IDs while your account is active. Order and invoice records are kept for up to 8 years for tax and accounting law. ID-proof documents are deleted when they are no longer needed for a quote or policy, or sooner if you ask. When you delete your account, we delete or anonymise your data unless the law requires us to keep it.</p>"),
  ("rights", "Your rights", f"<p>You can ask to access, correct, update or erase your personal data, withdraw consent, nominate someone to exercise your rights if you die or become unable to, and raise a grievance. You can change most details and your QR privacy settings in <a href=\"/account/\">your account</a>, or email <a href=\"mailto:{EMAIL}\">{EMAIL}</a>. If you are not satisfied with our response, you may complain to the Data Protection Board of India.</p>"),
  ("children", "Age requirement", "<p>You must be 18 or older to create an account or an ID. We do not knowingly collect personal data from children.</p>"),
  ("changes", "Changes to this policy", "<p>If we change this policy, we will update the date at the top. For important changes, we will tell you by email or WhatsApp before they take effect.</p>"),
  ("contact", "Contact and Grievance Officer", CONTACT),
  ])

terms = page("terms", "Terms of Use",
  f"The terms for using {BRAND}: free dog and cat ID cards, QR profiles, safety kit orders and insurance quotes.",
  "Terms of Use",
  f"These terms apply when you use {BRAND}, create an ID for your dog or cat, order a kit or request an insurance quote. By using the site you agree to them.",
  ["The ID card is free and private. It is not a government or municipal registration.",
   "You are responsible for the accuracy of what you enter.",
   "WhatsApp vet help and reminders do not replace a visit to your vet.",
   "We are not an insurer. Insurance is provided by licensed insurers, and their policy wording applies.",
   f"The safety kit costs {KIT_PRICE} including delivery and is paid on delivery."],
  [
  ("about", "About us", f"<p>{BRAND} is operated by {COMPANY}, {ADDRESS}. \"You\" means the person using the site.</p>"),
  ("eligibility", "Who can use the service", "<p>You must be at least 18 years old and able to enter into a binding contract under Indian law. You may create IDs only for dogs and cats you own or care for.</p>"),
  ("account", "Your account", "<p>An account is created when you make your first ID. Keep your password private; you are responsible for activity under your account. Tell us at once if you think someone else has used it. We may suspend accounts that break these terms.</p>"),
  ("id", "The free ID card and records", "<p>The ID card, ID number, QR profile and health record are a private service from us. They are <b>not</b> a government, municipal or Kennel Club registration and do not replace any licence your city requires. You are responsible for keeping the information accurate and up to date, including vaccination dates. Reminder schedules are suggestions; follow your vet's advice.</p>"),
  ("qr", "QR profiles and lost-and-found", "<p>Your QR profile is public and shows the details you choose. We help finders contact you but cannot guarantee that a lost dog or cat will be found or returned, and we are not responsible for what finders do.</p>"),
  ("vet", "Vet information and WhatsApp vet help", "<p>Information on the site and any WhatsApp vet assistance is for general guidance and triage. It is not a diagnosis and does not replace an examination. In an emergency, go to the nearest veterinary clinic straight away.</p>"),
  ("insurance", "Insurance", "<p>We are not an insurance company. Quote requests are passed to licensed insurers through an IRDAI-registered intermediary, whose details are shared with your quote. The insurer decides whether to offer cover and at what premium. Cover, exclusions, waiting periods, claims and cancellations are governed only by the insurer's policy wording. Membership benefits and partner clinic discounts are not insurance.</p>"),
  ("orders", "Safety kit orders", f"<p>The safety kit contains two QR ID tags, a reflective collar, a PVC ID card and a travel water bottle. It costs <b>{KIT_PRICE}</b>, inclusive of taxes and delivery anywhere we deliver in India, and is paid in cash or UPI on delivery. Tags and the card are personalised with the details you give us, so please check spellings. An order is confirmed when we accept it; we may contact you to confirm, and we may cancel an order if the address is not serviceable, the details look fraudulent or the item is unavailable. Product images are illustrations; colours and designs may vary slightly. Delivery, cancellations, returns and refunds are covered by our <a href=\"/refund-policy/\">Returns &amp; Refund Policy</a>.</p>"),
  ("content", "Your content", "<p>You keep ownership of the photos and documents you upload. You allow us to store, display and use them only to provide the service, including showing the photo on the QR profile and sharing documents with an insurer when you ask for a quote. Only upload content you have the right to share.</p>"),
  ("use", "Acceptable use", "<p>Do not misuse the site: no false information, impersonation, attempts to access other people's accounts or data, scraping, uploading malware, or using QR profiles to harass anyone.</p>"),
  ("ip", "Our intellectual property", f"<p>The {BRAND} name, logo, designs, illustrations and software belong to {COMPANY}. You may not copy or reuse them without our written permission.</p>"),
  ("liability", "Disclaimers and liability", f"<p>The service is provided \"as is\". To the extent the law allows, we are not liable for indirect or consequential losses, and our total liability for any claim relating to an order is limited to the amount you paid for that order. Nothing in these terms limits rights you have under the Consumer Protection Act, 2019.</p>"),
  ("indemnity", "Indemnity", "<p>You agree to compensate us for losses caused by your breach of these terms or by information you provide that is false or that you had no right to share.</p>"),
  ("law", "Governing law and disputes", "<p>These terms are governed by the laws of India. Courts at Jammu, Jammu and Kashmir, have jurisdiction, subject to your rights to approach consumer forums.</p>"),
  ("changes", "Changes", "<p>We may update these terms. The date at the top shows the latest version; continuing to use the site means you accept the updated terms.</p>"),
  ("contact", "Contact and grievances", CONTACT),
  ])

refund = page("refund-policy", "Returns, Refunds & Shipping Policy",
  f"Shipping, cancellation, returns and refunds for the {BRAND} safety kit, and how insurance refunds work.",
  "Returns, Refunds &amp; Shipping",
  "How we deliver the safety kit, how to cancel or exchange, and how refunds work.",
  [f"The kit costs {KIT_PRICE} including delivery, paid in cash or UPI on delivery.",
   "Cancel free of charge any time before your order is dispatched.",
   "Damaged, faulty or wrong item? Tell us within 48 hours of delivery for a free replacement or full refund.",
   "Collar too small or big? Exchange it free once within 7 days.",
   "Personalised tags and cards can't be returned for a change of mind."],
  [
  ("shipping", "Shipping and delivery", "<p>We deliver to serviceable PIN codes across India. Delivery is included in the price. Because tags and the card are engraved and printed for your dog or cat, we usually dispatch within 3–5 working days of confirming your order, and delivery usually takes a further 3–7 working days depending on your location. We send dispatch and tracking updates on WhatsApp. If your PIN code is not serviceable, we will tell you and cancel the order.</p>"),
  ("cod", "Paying on delivery", "<p>Please keep the exact amount ready in cash, or pay by UPI to the courier when the parcel arrives. Check that the parcel is sealed before paying. If an order is refused at delivery without a valid reason, we may ask for advance payment on future orders.</p>"),
  ("cancel", "Cancelling an order", f"<p>You can cancel free of charge until your order is dispatched. Email <a href=\"mailto:{EMAIL}\">{EMAIL}</a> with your order number (it starts with ORD-). After dispatch, you can refuse the delivery or follow the returns process below.</p>"),
  ("returns", "Returns", "<p>The ID tags and PVC card are personalised, so we can't accept them back if you change your mind. We will always put things right when:</p><ul><li>an item arrives <b>damaged or faulty</b>;</li><li>you receive the <b>wrong item</b>, or something is missing;</li><li>the engraving or printing is <b>wrong because of our error</b>.</li></ul><p>Tell us within <b>48 hours of delivery</b> with your order number and photos (an unboxing video helps). We will send a free replacement or give a full refund, and arrange collection if needed. If the details you entered were wrong, we can re-engrave at cost.</p>"),
  ("exchange", "Collar size exchange", "<p>If the collar doesn't fit, you can exchange it for another size once, free of charge, within 7 days of delivery, as long as it is unused and in its original condition. Email us with your order number and the size you need.</p>"),
  ("refunds", "Refunds", "<p>Approved refunds are processed within 7 working days. For cash-on-delivery orders, we refund to your UPI ID or bank account. For prepaid orders, we refund to the original payment method. Your bank may take a few more days to show the money.</p>"),
  ("free", "The free ID card", "<p>The digital ID card, QR profile, health record and reminders are free, so there is nothing to refund. You can delete them at any time.</p>"),
  ("insurance", "Insurance premiums", "<p>Insurance premiums are paid to the insurer, not to us. Cancellations and premium refunds, including any free-look period, follow the insurer's policy wording. We are happy to help you contact the insurer.</p>"),
  ("contact", "Contact", CONTACT),
  ])

for slug, html in [("privacy-policy", privacy), ("terms", terms), ("refund-policy", refund)]:
    os.makedirs(os.path.join(ROOT, slug), exist_ok=True)
    with open(os.path.join(ROOT, slug, "index.html"), "w") as f:
        f.write(html)
print("built")
