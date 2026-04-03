#!/bin/bash

# 🎧 Script de lancement du webhook Stripe pour le projet Vinyls
# Usage: ./scripts/stripe-webhook.sh

echo "🦞 Vinyls Stock - Stripe Webhook Listener"
echo "=========================================="
echo ""

# Vérifier si stripe CLI est installé
if ! command -v stripe &> /dev/null; then
    echo "❌ Stripe CLI n'est pas installé."
    echo "Installez-le avec :"
    echo "  macOS: brew install stripe/stripe-cli/stripe"
    echo "  Linux: https://github.com/stripe/stripe-cli#installation"
    exit 1
fi

# Vérifier si connecté
echo "🔐 Vérification de la connexion Stripe..."
stripe config --list &> /dev/null
if [ $? -ne 0 ]; then
    echo "⚠️  Vous n'êtes pas connecté à Stripe."
    echo "Exécutez d'abord : stripe login"
    echo ""
    stripe login
fi

echo ""
echo "🚀 Lancement de l'écoute des webhooks..."
echo "📡 Forwarding vers : http://localhost:8000/stripe/webhook"
echo ""
echo "💡 Astuces :"
echo "  - Testez un paiement : stripe trigger checkout.session.completed"
echo "  - Le secret webhook sera affiché ci-dessous"
echo "  - Copiez-le dans votre .env : STRIPE_WEBHOOK_SECRET=whsec_..."
echo ""
echo "=========================================="
echo ""

# Lancer l'écoute
stripe listen --forward-to http://localhost:8000/stripe/webhook
