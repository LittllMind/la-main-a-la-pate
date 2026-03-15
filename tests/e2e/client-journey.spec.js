import { test, expect } from '@playwright/test';

// T12.4-B Tests E2E — Parcours Client Complet
// RED: Test écrit avant implémentation

test.describe('Parcours Client Complet', () => {
  
  const clientEmail = 'client.test@example.com';
  const clientPassword = 'password123';

  test('connexion client réussie', async ({ page }) => {
    // Given: Une page de connexion accessible
    await page.goto('http://127.0.0.1:8000/login');
    
    // When: Le client remplit ses identifiants
    await page.fill('input[name="email"], input[type="email"]', clientEmail);
    await page.fill('input[name="password"], input[type="password"]', clientPassword);
    await page.click('button[type="submit"], button:has-text("Connexion")');
    
    // Then: Le client est connecté (redirection ou message de bienvenue)
    await expect(page).toHaveURL(/dashboard|kiosque|accueil/);
    await expect(page.locator('text=Bienvenue, text=' + clientEmail)).toBeVisible();
  });

  test('connexion client échoue avec mauvais identifiants', async ({ page }) => {
    // Given: Une page de connexion accessible
    await page.goto('http://127.0.0.1:8000/login');
    
    // When: Le client remplit de mauvais identifiants
    await page.fill('input[name="email"], input[type="email"]', 'mauvais@email.com');
    await page.fill('input[name="password"], input[type="password"]', 'mauvaismdp');
    await page.click('button[type="submit"]');
    
    // Then: Une erreur est affichée
    await expect(page.locator('text=identifiants invalides, text=erreur, .error, .alert-danger')).toBeVisible();
    await expect(page).toHaveURL(/login/);
  });

  test('ajout au panier depuis le kiosque', async ({ page }) => {
    // Given: Un client connecté sur le kiosque
    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name="email"]', clientEmail);
    await page.fill('input[name="password"]', clientPassword);
    await page.click('button[type="submit"]');
    
    // When: Le client ajoute un vinyle au panier
    await page.goto('http://127.0.0.1:8000/kiosque');
    const addButton = page.locator('button:has-text("Ajouter"), button:has-text("+"), .add-to-cart').first();
    await addButton.click();
    
    // Then: Le panier contient 1 article
    const cartCount = page.locator('.cart-count, [data-cart-count], .badge-cart');
    await expect(cartCount).toHaveText('1');
  });

  test('passage de commande complet', async ({ page }) => {
    // Given: Un client connecté avec un article dans le panier
    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name="email"]', clientEmail);
    await page.fill('input[name="password"]', clientPassword);
    await page.click('button[type="submit"]');
    
    await page.goto('http://127.0.0.1:8000/kiosque');
    await page.click('button:has-text("Ajouter"), button:has-text("+")').first();
    
    // When: Le client valide sa commande
    await page.click('a:has-text("Panier"), a[href*="panier"], a[href*="cart"]');
    await page.click('button:has-text("Commander"), button:has-text("Valider"), button[type="submit"]');
    
    // Then: La commande est créée avec un numéro
    await expect(page).toHaveURL(/commande|order|confirmation/);
    await expect(page.locator('text=commande, text=confirmée, text=numéro')).toBeVisible();
  });

  test('vérification de la commande dans historique', async ({ page }) => {
    // Given: Un client connecté avec une commande existante
    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name="email"]', clientEmail);
    await page.fill('input[name="password"]', clientPassword);
    await page.click('button[type="submit"]');
    
    // When: Le client consulte ses commandes
    await page.goto('http://127.0.0.1:8000/mes-commandes');
    
    // Then: La liste des commandes s'affiche
    await expect(page.locator('.commande, .order, tr, table')).toBeVisible();
    await expect(page.locator('text=Aucune commande')).not.toBeVisible();
  });

});