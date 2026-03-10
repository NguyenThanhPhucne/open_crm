# CRM AI AutoComplete - Getting Started Guide

## Quick Start (5 minutes)

### 1. Enable the Module

```bash
cd /path/to/open_crm
drush pm-enable crm_ai
```

Or via Drupal Admin:

- Navigate to `/admin/modules`
- Search for "CRM AI AutoComplete"
- Check the box and click "Install"

### 2. Grant Permissions

Navigate to `/admin/people/permissions`:

1. Find "CRM AI AutoComplete" section
2. Check "Use CRM AI AutoComplete" for desired roles:
   - ☑ Sales Rep
   - ☑ Sales Manager
   - ☑ Administrator

3. Click "Save permissions"

### 3. Test with Mock Provider (No API needed!)

1. Navigate to any Contact form:
   - `/node/add/contact` (new)
   - `/node/123/edit` (existing, if ID is 123)

2. Fill in a few fields:
   - Title: "John Smith"
   - Company (field_company): "ABC Corp"
   - Notes (field_notes): "Interested in sales CRM"

3. Look for **✨ AI Complete** button (should be in form actions)

4. Click it!

5. Watch fields auto-populate with suggestions:
   - field_email → "john@smith@example.com"
   - field_phone → "+1 (555) 123-4567"
   - field_job_title → "Sales Manager"

6. Review highlighted fields (blue background, "AI Suggested" badge)

7. Click ✕ Clear to remove any suggestion you don't like

8. Save the form normally

### 4. (Optional) Configure Real AI Provider

Navigate to `/admin/config/crm/ai`:

**For OpenAI**:

1. Provider: Select "OpenAI"
2. API Key: [Get here](https://platform.openai.com/api-keys)
   - Click "Create new secret key"
   - Copy and paste into field
3. Model: `gpt-3.5-turbo` (or `gpt-4`)
4. Temperature: `0.7`
5. Click "Save configuration"

**For Anthropic**:

1. Provider: Select "Anthropic"
2. API Key: [Get here](https://console.anthropic.com/)
   - Click "View API key"
   - Copy and paste
3. Model: `claude-3-haiku-20240307`
4. Click "Save configuration"

## Using the Feature

### For Contacts/Leads

1. Open a Contact form (`/node/add/contact`)
2. Enter basic info:
   ```
   Title: "Jane Doe"
   Company: "Tech Startup Inc"
   Notes: "Referred by client, needs CRM for 5 people"
   ```
3. Click ✨ AI Complete
4. AI suggests:
   - Email: jane@techstartup.com
   - Phone: +1 (555) 987-6543
   - Job Title: Founder
   - Industry: Technology
   - Company Size: 5-10 employees

### For Deals

1. Open a Deal form (`/node/add/deal`)
2. Enter:
   ```
   Title: "ABC Corp - CRM Implementation"
   Company: "ABC Corporation"
   Notes: "Initial consultation completed, ready for proposal"
   ```
3. Click ✨ AI Complete
4. AI suggests:
   - Stage: Proposal
   - Value: $25,000
   - Probability: 70%
   - Expected Close Date: 2026-04-15

### For Organizations

1. Open Organization form
2. Enter:
   ```
   Title: "MegaCorp LLC"
   Description: "Manufacturing company in the midwest"
   ```
3. Click ✨ AI Complete
4. Suggestions:
   - Industry: Manufacturing
   - Website: megacorp.com (structured appropriately)
   - Employee Count: 500-1000
   - Annual Revenue: $50M-$100M

## Understanding the UI

### The AI Complete Button

```
Stage: Qualification    [ ✨ AI Complete ]
```

- **Sparkle Icon (✨)**: Indicates AI feature
- **Button Text**: "AI Complete"
- **Location**: Form actions section
- **State When Loading**: Grayed out with spinner

### Field Highlighting

After clicking AI Complete:

```
[John Smith@example.com]
    AI Suggested    [✕ Clear]
```

**Visual Indicators**:

- **Blue Left Border**: AI-generated field
- **Light Blue Background**: AI suggestion applied
- **"AI Suggested" Badge**: Confidence level (e.g., "93% confidence")
- **Clear Button (✕)**: Remove this suggestion

### Status Messages

**Success**:

```
✓ Fields updated with AI suggestions
```

(Auto-disappears after 5 seconds)

**Error**:

```
Error: API call failed. Please try again.
```

(Auto-disappears after 7 seconds)

## Common Use Cases

### Case 1: Cold Lead Entry

User receives lead from:

- Website form
- Event signup
- Email inquiry

**Workflow**:

1. Manually enter: Name, Company, Notes
2. Click AI Complete
3. AI fills: Email, Phone, Job Title, Industry
4. Save and create follow-up task

**Time Saved**: ~2 minutes per lead

### Case 2: Quick Deal Entry

User creates deal from verbal conversation:

**Workflow**:

1. Enter: Contact name, Company, Deal size estimate
2. Click AI Complete
3. AI fills: Stage, Probability, Timeline
4. Review and adjust as needed

**Time Saved**: ~1 minute per deal

### Case 3: Batch Organization Import

After CSV import of company list:

**Workflow**:

1. Import organizations with just names
2. Edit each org, click AI Complete
3. AI fills: Industry, Website, Size
4. Verify and save

**Time Saved**: ~30 seconds per org vs 5+ minutes manual

## FAQ

### Q: Do I need an API key to use this?

**A**: No! The Mock provider works without any API key. It returns realistic suggestions for testing. For production, you can use OpenAI or Anthropic with paid tiers.

### Q: What if the AI suggestion is wrong?

**A**: Click the ✕ Clear button next to the field to remove it, then manually enter the correct value.

### Q: Can I undo after saving?

**A**: Yes! Just edit the entity again and fix the fields.

### Q: How often can I use AI Complete?

**A**: By default, 10 times per hour per user. This can be increased in admin settings.

### Q: Will suggestions overwrite my data?

**A**: No! AI suggestions only fill EMPTY fields. If a field already has a value, it won't be changed.

### Q: Is my data sent to external APIs?

**A**: Only if you configure OpenAI/Anthropic. With Mock provider, everything stays local. When using external APIs, only the form field values are sent (no user data beyond what you enter).

### Q: Can I customize the suggestions?

**A**: In future versions, yes. Currently, you can only accept/reject/clear suggestions.

## Troubleshooting

### Button is missing

**Check**:

1. Is module enabled? `/admin/modules` → search "CRM AI"
2. Do you have permission? `/admin/people/permissions` → search "CRM AI"
3. Are you editing an existing entity? (Button only shows on existing entities)

**Fix**: Enable module and grant permission to your role

### Suggestions are always generic

**Possible causes**:

1. Using Mock provider (default)
2. API key not configured

**Fix**: Go to `/admin/config/crm/ai` and:

- Select real provider (OpenAI or Anthropic)
- Enter valid API key
- Click Save

### API key error

**Error message**: "Invalid API key"

**Fix**:

1. Verify key is correct (copy-paste from provider directly)
2. Verify correct provider selected
3. Check that key is active (not revoked)
4. Clear cache: `drush cr`

### Rate limit error

**Error message**: "Rate limit exceeded"

**Cause**: User exceeded 10 requests/hour

**Fix**:

1. Wait 1 hour
2. Or increase limit: `/admin/config/crm/ai` → "Rate limit per hour"

## Advanced Configuration

### View Logs

```bash
# See all CRM AI activity
drush dblog-show crm_ai

# Or in UI: /admin/reports/dblog (filter by crm_ai)
```

### Clear Cache

```bash
# Clear all caches (including AI suggestions)
drush cache-clear all

# Or just default cache
drush cache-clear default
```

### Adjust Rate Limit

1. Go to `/admin/config/crm/ai`
2. Change "Rate limit per hour" field
3. Click Save

### Test with API

```bash
curl -X POST http://yoursite/api/crm/ai/autocomplete \
  -H "Content-Type: application/json" \
  -d '{
    "entityType": "contact",
    "fields": {
      "title": "Test Person",
      "field_company": "Test Corp"
    }
  }'
```

## Next Steps

1. **Test with all entity types**: Contacts, Deals, Organizations, Activities
2. **Invite team**: Grant "Use CRM AI AutoComplete" permission
3. **Configure real provider**: Set up OpenAI or Anthropic with API key
4. **Gather feedback**: Ask team what fields are most useful to auto-complete
5. **Adjust settings**: Fine-tune temperature and rate limits based on feedback

## Tips & Tricks

### Maximize Suggestion Quality

1. **Provide good context**: The more fields you fill before clicking AI Complete, the better the suggestions
2. **Use descriptive notes**: Include industry, company size, etc. in the Notes field
3. **Follow conventions**: Use standard field values for better matching

### Save Time

1. **Batch process**: Create multiple contacts, then use AI Complete on each
2. **Use for bulk imports**: After importing contacts, use AI Complete to fill incomplete data
3. **Create templates**: Save frequently-used values as drafts

### Quality Control

1. **Review before saving**: Always review AI suggestions
2. **Spot-check**: Periodically verify AI isn't making wrong assumptions
3. **Clear bad suggestions**: Use ✕ Clear for any wrong values

## Support

Need help?

1. **Check logs**: `/admin/reports/dblog` (filter for "crm_ai")
2. **Review README**: [README.md](README.md)
3. **Check settings**: `/admin/config/crm/ai`
4. **Clear cache**: `drush cache-clear all`

## What's Next?

Future enhancements coming:

- ✓ Multiple LLM provider support (already built!)
- Custom field-level prompts
- Suggestion explanation tooltips
- Batch completion workflow
- Suggestion history/undo
- A/B testing of suggestions
- Integration with CRM workflows

---

**Enjoy faster CRM data entry with AI! 🚀**
