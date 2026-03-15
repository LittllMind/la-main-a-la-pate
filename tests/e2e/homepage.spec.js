import { test, expect } from '@playwright/test';

// T12.4-A Tests E2E — Homepage
// RED: Test écrit avant implémentation

test.describe('Page d\'accueil', () => {
  
  test('la page d\'accueil charge correctement', async ({ page }) => {
    // Given: L'application est accessible
    // When: On accède à la page d'accueil
    await page.goto('http://127.0.0.1:8000/');
    
    // Then: La page se charge avec titre approprié
    await expect(page).toHaveTitle(/Vinyles Stock|Accueil/);
  });

  test('la page affiche le lien vers le kiosque', async ({ page }) => {
    // When: On accède à la page d'accueil
    await page.goto('http://127.0.0.1:8000/');
    
    // Then: Le lien Kiosque est visible
    const kiosqueLink = page.locator('a[href*="kiosque"], a:text("Kiosque")').first();
    await expect(kiosqueLink).toBeVisible();
  });

  test('la page affiche le lien de connexion admin', async ({ page }) => {
    // When: On accède à la page d'accueil
    await page.goto('http://127.0.0.1:8000/');
    
    // Then: Le lien Connexion est visible
    const loginLink = page.locator('a[href*="login"], a:text("Connexion")').first();
    await expect(loginLink).toBeVisible();
  });

});