# 🚀 AI AutoComplete - Quick Start (5 Minutes)

## Installation

### Step 1: Enable Module (30 seconds)

```bash
ddev exec drush en crm_ai_autocomplete -y
ddev exec drush cr
```

### Step 2: Get API Key (2 minutes)

**Option A: OpenAI (Recommended)**

1. Go to https://platform.openai.com/api-keys
2. Sign up or login
3. Create new API key
4. Copy the key (save securely!)

**Option B: Anthropic (Claude)**

1. Go to https://console.anthropic.com
2. Sign up or login
3. Create new API key
4. Copy the key

### Step 3: Configure (2 minutes)

1. Go to `/admin/config/ai/autocomplete`
2. Select your AI provider
3. Paste your API key
4. Click **Save**

### Step 4: Grant Permission (30 seconds)

1. Go to `/admin/people/permissions`
2. Find "Use CRM AI AutoComplete"
3. Check for your user role
4. Click **Save permissions**

## ✅ You're Done!

### Test It Now

1. Go to any Contact, Deal, or Organization form
2. Fill in 2 fields (e.g., Name + Company)
3. Click **"✨ AI Complete"** button
4. Watch it auto-fill other fields!

---

## What Just Happened?

```
Form                         AI System               Your Database
─────────────────────────────────────────────────────────────
User fills name
  + company          ──→    Smart prompt       ──→
                            "You are a CRM AI..."
                            "Name: John Smith"
                            "Company: Acme"
                            "Suggest: industry,
                             source, lead score"
                                 ↓
                            OpenAI/Claude API
                                 ↓
                            Returns JSON
                            {
                              "industry": "Tech",
                              "source": "Web",
                              "lead_score": 85
                            }
                      ←── Form auto-fills ←───
                           marked ✨

User reviews & clicks Save  ──→  Entity created
                                 with AI suggestions
```

---

## Features

✅ **Auto-fill empty fields** - AI analyzes what you entered  
✅ **Multi-provider** - Works with OpenAI, Anthropic, or your API  
✅ **Works on all forms** - Contact, Deal, Organization, custom entities  
✅ **Safety first** - Doesn't overwrite existing values  
✅ **Mobile friendly** - Works on phones and tablets  
✅ **Beautiful UI** - Gradient buttons, toast notifications, animations

---

## Troubleshooting

| Problem                      | Solution                                         |
| ---------------------------- | ------------------------------------------------ |
| Button not showing           | Grant permission + clear cache (`drush cr`)      |
| Nothing happens when clicked | Check API key in `/admin/config/ai/autocomplete` |
| Fields not auto-filling      | Try filling 3+ fields instead of 2               |
| Slow response                | Check timeout in config (default: 15 sec)        |
| Wrong suggestions            | Try different AI model (GPT-4 vs 3.5-turbo)      |

---

## Advanced Config

Go to `/admin/config/ai/autocomplete` to adjust:

- 🔌 **AI Provider** - Switch between OpenAI/Anthropic/Custom
- 🤖 **Model** - Choose specific AI model version
- 📋 **Entity Types** - Select which forms get the button
- ⏱️ **Timeout** - How long to wait for AI (default: 15s)
- 🔍 **Min Fields** - How many must be filled first (default: 2)
- 📝 **Custom Prompt** - Add domain-specific instructions

---

## Pro Tips

1. **Fill 3-4 fields first** for better AI suggestions
2. **Use OpenAI GPT-4** if available (better quality)
3. **Review suggestions** before saving (AI can hallucinate)
4. **Check logs** if issues: `/admin/reports/dblog`

---

## What's Being Sent to AI?

Your form data:

```json
{
  "First Name": "John Smith",
  "Company": "Acme Inc",
  "Phone": "(555) 123-4567"
}
```

Request to AI (no sensitive data exposed):

```
"You are a CRM assistant.
Given this partial contact info:
- First Name: John Smith
- Company: Acme Inc
- Phone: (555) 123-4567

Suggest values for these missing fields:
- Industry
- Lead Source
- Lead Score"
```

**Your data stays in your Drupal instance. Only summaries sent to AI.**

---

**Need help?** Check the full guide: `AI_AUTOCOMPLETE_IMPLEMENTATION_GUIDE.md`
