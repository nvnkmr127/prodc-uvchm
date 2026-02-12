#!/bin/bash

echo "📤 Push UVCHM Code to GitHub"
echo "============================"

cd /home/digiclou/uvchm.digicloudify.com

# Check git status
echo "📋 Current git status:"
git status --short

echo ""
read -p "Enter commit message: " commit_message

if [ -z "$commit_message" ]; then
    commit_message="Update: $(date +'%Y-%m-%d %H:%M:%S')"
fi

echo ""
echo "🔄 Adding files to git..."
git add .

echo "📝 Creating commit..."
git commit -m "$commit_message"

echo "📤 Pushing to GitHub..."
git push origin main

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Successfully pushed to GitHub!"
    echo "🌐 Repository: https://github.com/nvnkmr127/uvchm-live-app"
    echo ""
    echo "🚀 To deploy to production, run on production server:"
    echo "   ./quick-deploy-production.sh"
else
    echo ""
    echo "❌ Failed to push to GitHub"
    exit 1
fi
