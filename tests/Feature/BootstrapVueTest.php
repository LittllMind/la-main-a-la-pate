<?php

namespace Tests\Feature;

use Tests\TestCase;

class BootstrapVueTest extends TestCase
{
    protected $migrate = false; // Pas besoin de migrations pour ces tests
    /**
     * Test 2.1.1: Bootstrap CSS est chargé
     */
    public function test_bootstrap_css_est_charge()
    {
        // Vérifier que Bootstrap est dans node_modules
        $this->assertDirectoryExists(
            base_path('node_modules/bootstrap'),
            'Bootstrap doit être installé via npm'
        );

        // Vérifier que l'import Bootstrap est dans le CSS
        $cssContent = file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString("bootstrap", $cssContent, 'app.css doit importer Bootstrap');
    }

    /**
     * Test 2.1.2: Vue.js 3 est installé
     */
    public function test_vue_js_est_installe()
    {
        // Vérifier que Vue.js est dans node_modules
        $this->assertDirectoryExists(
            base_path('node_modules/vue'),
            'Vue.js 3 doit être installé via npm'
        );

        // Vérifier le package.json
        $packageJson = json_decode(file_get_contents(base_path('package.json')), true);
        $this->assertArrayHasKey('vue', $packageJson['dependencies'], 'Vue.js doit être dans les dépendances');
        $this->assertStringStartsWith('^3', $packageJson['dependencies']['vue'], 'Vue.js doit être en version 3');
    }

    /**
     * Test 2.1.3: Vite plugin Vue est configuré
     */
    public function test_vite_plugin_vue_est_configure()
    {
        // Vérifier le vite.config.js contient le plugin Vue
        $viteConfig = file_get_contents(base_path('vite.config.js'));
        $this->assertStringContainsString("@vitejs/plugin-vue", $viteConfig, 'vite.config.js doit importer le plugin Vue');
        $this->assertStringContainsString("from '@vitejs/plugin-vue'", $viteConfig, 'vite.config.js doit importer depuis @vitejs/plugin-vue');
        $this->assertMatchesRegularExpression('/plugins:\s*\[[\s\S]*vue\(/', $viteConfig, 'vite.config.js doit utiliser le plugin Vue');
    }

    /**
     * Test 2.1.4: Test component Vue existe
     */
    public function test_component_vue_test_existe()
    {
        // Vérifier que le test component existe
        $this->assertFileExists(
            resource_path('js/components/TestComponent.vue'),
            'Le composant de test Vue doit exister'
        );

        // Vérifier que le composant importé dans app.js
        $appJs = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString("TestComponent", $appJs, 'TestComponent doit être importé dans app.js');
        $this->assertStringContainsString("createApp", $appJs, 'Vue createApp doit être utilisé');
    }

    /**
     * Test 2.1.5: App.js monte l'application Vue
     */
    public function test_app_js_monte_application_vue()
    {
        $appJs = file_get_contents(resource_path('js/app.js'));
        
        // Vérifier la structure Vue 3
        $this->assertStringContainsString("import { createApp }", $appJs, 'createApp doit être importé depuis vue');
        $this->assertStringContainsString("app.mount(", $appJs, 'L\'application Vue doit être montée');
    }
}
