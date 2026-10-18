-- Adds editable policy pages and FAQs to a database created before they existed.

SET NAMES utf8mb4;

-- Editable content pages (privacy policy, terms, refund policy, allergens).
-- Body is HTML written by the shop; {store_phone}-style tokens are filled from settings.
CREATE TABLE IF NOT EXISTS pages (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug             VARCHAR(80) NOT NULL UNIQUE,
    title            VARCHAR(160) NOT NULL,
    summary          VARCHAR(255) NULL,
    body             MEDIUMTEXT NOT NULL,
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    meta_title       VARCHAR(160) NULL,
    meta_description VARCHAR(255) NULL,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faqs (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    category   VARCHAR(60) NOT NULL,
    question   VARCHAR(255) NOT NULL,
    answer     TEXT NOT NULL,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    KEY idx_faqs_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pages (slug, title, summary, body) VALUES
('privacy-policy', 'Privacy Policy', 'How we collect, use and protect your personal information.', '<p>{store_name} ("we", "us") respects your privacy. This policy explains what personal information we collect when you use our website or place an order, and how we handle it in line with the Australian Privacy Principles in the <em>Privacy Act 1988</em> (Cth).</p>
<h2>Information we collect</h2>
<ul>
<li><strong>Contact details:</strong> your name, email address and phone number when you place an order, request a custom cake quote, send us a message or create an account.</li>
<li><strong>Order details:</strong> the cakes you order, messages for your cake, pickup or delivery date and time, delivery address (if used) and any notes you add.</li>
<li><strong>Payment information:</strong> card payments are processed by Stripe. We never see or store your full card number. If you pay by PayID or bank transfer, we keep the payment screenshot or receipt you upload.</li>
<li><strong>Photos you send us:</strong> reference images uploaded with a custom cake request.</li>
<li><strong>Account details:</strong> if you create an account, we store your details and a securely encrypted version of your password.</li>
</ul>
<h2>How we use your information</h2>
<ul>
<li>To prepare your order and have it ready at the time you chose.</li>
<li>To contact you about your order, your custom cake quote or your enquiry.</li>
<li>To check payments and keep records required for tax and accounting.</li>
<li>To show your order history when you log in to your account.</li>
</ul>
<p>We do not sell your personal information, and we do not send marketing emails unless you ask us to.</p>
<h2>Who we share it with</h2>
<p>We only share information with the services we need to run the shop, such as our payment provider (Stripe), our website host and our email provider. They may only use it to provide their service to us.</p>
<h2>Cookies</h2>
<p>Our website uses a small session cookie so your cart and login work while you browse. We do not use advertising cookies.</p>
<h2>Keeping your information safe</h2>
<p>We store your information on secure servers and limit access to staff who need it. Payment screenshots and reference photos are kept in a private area that is not publicly accessible.</p>
<h2>Accessing or correcting your information</h2>
<p>You can update your details from your account page, or contact us to see, correct or delete the information we hold about you.</p>
<h2>Questions or complaints</h2>
<p>Please contact us on {store_phone} or {store_email}. If you are not happy with our response, you can contact the Office of the Australian Information Commissioner at <a href="https://www.oaic.gov.au" target="_blank" rel="noopener">oaic.gov.au</a>.</p>'),
('terms', 'Terms and Conditions', 'The terms that apply when you order from our website.', '<p>These terms apply when you place an order with {store_name} through this website. By placing an order you agree to them.</p>
<h2>Orders</h2>
<ul>
<li>Please order at least {min_lead_hours} hours before your pickup time for cakes from our menu, and at least {custom_lead_days} days ahead for custom cakes.</li>
<li>Your order is confirmed once payment has been received. For PayID and bank transfer, this is after we have checked your payment screenshot.</li>
<li>We will contact you if we cannot fulfil your order as placed, for example if an ingredient is unavailable.</li>
</ul>
<h2>Prices and payment</h2>
<ul>
<li>All prices are in Australian dollars.</li>
<li>Prices shown at checkout are the prices you pay. Custom cakes are priced by quote.</li>
</ul>
<h2>Pickup</h2>
<ul>
<li>Please collect your order from {store_address} at the time you selected. If you need to change the time, call us on {store_phone} as early as possible.</li>
<li>Once collected, please keep cream and cheesecake products refrigerated and transport them on a flat surface.</li>
</ul>
<h2>Our cakes</h2>
<ul>
<li>Every cake is handmade, so colours, decorations and shapes may differ slightly from photos on our website.</li>
<li>For custom cakes, we will match your design as closely as we can. Characters and printed images may be adapted.</li>
<li>Our products are made in a kitchen that handles common allergens. Please read our <a href="{url:allergens}">allergen information</a> before ordering.</li>
</ul>
<h2>Cancellations and refunds</h2>
<p>Please see our <a href="{url:refund-policy}">Refund and Cancellation Policy</a>.</p>
<h2>Your rights</h2>
<p>Nothing in these terms limits your rights under the Australian Consumer Law.</p>
<h2>Contact</h2>
<p>{store_name}, {store_address}. Phone {store_phone}, email {store_email}.</p>'),
('refund-policy', 'Refund and Cancellation Policy', 'What to do if you need to cancel or have a problem with your order.', '<p>We want you to love your cake. This policy explains what happens if you need to cancel or if something is not right with your order.</p>
<h2>Cancelling or changing an order</h2>
<p>Please contact us on {store_phone} as early as possible if you need to cancel or change your order. Because every cake is made to order, we may not be able to offer a refund once we have started preparing your cake, buying special ingredients or making decorations.</p>
<h2>Custom cakes</h2>
<p>Custom cakes take extra preparation. Any cancellation terms for your custom cake will be explained when we send your quote.</p>
<h2>If something is wrong</h2>
<p>If your order is not what you ordered, is damaged when you collect it, or there is a problem with quality, please let us know straight away, ideally before you leave the shop or on the same day, and keep the cake if you can. We will work with you to fix it with a replacement, a credit or a refund.</p>
<h2>Refunds</h2>
<p>Approved refunds are returned to the original payment method. Card refunds usually appear within 5 to 10 business days, depending on your bank.</p>
<h2>Your rights</h2>
<p>Our products come with guarantees that cannot be excluded under the Australian Consumer Law. This policy does not limit those rights.</p>'),
('allergens', 'Allergen Information', 'Important information for customers with food allergies or intolerances.', '<p>Your safety matters to us. Please read this before ordering if you or your guests have a food allergy or intolerance.</p>
<h2>Our kitchen</h2>
<p>Our cakes are made in a kitchen that handles common allergens, including milk, eggs, wheat (gluten), soy and tree nuts such as hazelnuts, almonds and pistachios. Because we use shared equipment and work areas, <strong>we cannot guarantee that any product is completely free from traces of allergens</strong>.</p>
<h2>Gluten-free, eggless and vegan cakes</h2>
<p>We can make gluten-free, eggless and vegan options on request. Please call us on {store_phone} before ordering so we can confirm what is possible for your cake. These cakes are made in the same kitchen, so they may still contain traces of other allergens.</p>
<h2>Halal</h2>
<p>All our products are made with 100% Halal ingredients.</p>
<h2>Ask us</h2>
<p>If you have any questions about ingredients, please call us or ask in store before you order. Add any allergy details to the notes at checkout so our team can see them.</p>');

INSERT INTO faqs (category, question, answer, sort_order) VALUES
('Ordering', 'How far in advance should I order?', '<p>Please order at least {min_lead_hours} hours ahead for cakes from our menu, and at least {custom_lead_days} days ahead for custom cakes. For weddings and big events, the earlier the better.</p>', 1),
('Ordering', 'I need a cake today. Can you help?', '<p>Please call us on {store_phone} and we will let you know what we can do.</p>', 2),
('Ordering', 'Can I add a message on my cake?', '<p>Yes, a message is free on every cake. Add it on the cake''s page before adding it to your cart.</p>', 3),
('Ordering', 'Can I change the size after adding a cake to my cart?', '<p>Yes. Open your cart and choose a different size from the dropdown next to the cake.</p>', 4),
('Pickup and delivery', 'Where do I collect my order?', '<p>From our shop at {store_address}. You choose the pickup date and time at checkout.</p>', 5),
('Pickup and delivery', 'Can I change my pickup time?', '<p>Please call us on {store_phone} as early as possible and we will do our best to help.</p>', 6),
('Pickup and delivery', 'Do you deliver?', '<p>We deliver across the ACT, Queanbeyan and surrounding areas. Call us on {store_phone} to arrange delivery.</p>', 7),
('Payment', 'How can I pay?', '<p>You can pay online at checkout. The payment options shown at checkout are the ones currently available.</p>', 8),
('Payment', 'When is my order confirmed?', '<p>Card payments are confirmed straight away. For PayID and bank transfer, we confirm your order once we have checked your payment screenshot.</p>', 9),
('Dietary needs', 'Are your cakes Halal?', '<p>Yes, all our products are made with 100% Halal ingredients.</p>', 10),
('Dietary needs', 'Do you make gluten-free, eggless or vegan cakes?', '<p>Yes, on request. Please call us before you order so we can confirm the options for your cake.</p>', 11),
('Dietary needs', 'I have a food allergy. Is it safe to order?', '<p>Our kitchen handles common allergens, so we cannot guarantee any cake is free from traces. Please read our <a href="{url:allergens}">allergen information</a> and call us before ordering.</p>', 12),
('Custom cakes', 'How do I order a custom cake?', '<p>Fill in our <a href="{url:custom-cakes}">custom cake form</a>, call us, or visit the shop. We will reply with a quote and help you choose the size, flavour and decorations.</p>', 13),
('Custom cakes', 'Can you make a cake from a photo?', '<p>Yes. Upload a photo with your request and we will match the design as closely as we can.</p>', 14),
('Cancellations', 'Can I cancel my order?', '<p>Please contact us as early as possible. See our <a href="{url:refund-policy}">Refund and Cancellation Policy</a> for details.</p>', 15);
