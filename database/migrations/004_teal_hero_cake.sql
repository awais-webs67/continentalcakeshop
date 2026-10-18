-- Homepage hero uses the drip cake recoloured to the shop's teal (only if the admin has not chosen another photo).
UPDATE settings SET setting_value = 'gallery/hero-cake-teal.webp' WHERE setting_key = 'hero_image' AND setting_value = 'gallery/custom-24.webp';
