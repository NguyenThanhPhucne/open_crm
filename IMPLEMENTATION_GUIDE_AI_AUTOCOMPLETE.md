# CRM AI AutoComplete - Implementation Guide

## Overview

This guide walks through implementing the AI AutoComplete feature in your Drupal 11 CRM system. The feature allows users to automatically generate missing fields using AI across all entity types.

## Architecture

### Module Structure

```
crm_ai/
├── crm_ai.info.yml                    # Module metadata
├── crm_ai.module                      # Form hooks
├── crm_ai.routing.yml                 # API routes
├── crm_ai.services.yml                # Service definitions
├── crm_ai.libraries.yml               # JavaScript/CSS libraries
├── crm_ai.permissions.yml             # Permission definitions
├── crm_ai.install                     # Installation hooks
├── README.md                          # User documentation
│
├── src/
│   ├── Service/
│   │   ├── AIEntityAutoCompleteService.php        # Main service
│   │   ├── EntitySchemaService.php                # Schema management
│   │   ├── FieldValidatorService.php              # Field validation
│   │   └── LLMProviderService.php                 # LLM integration
│   │
│   ├── Controller/
│   │   └── AIAutoCompleteController.php           # API endpoints
│   │
│   ├── Form/
│   │   └── AIConfigForm.php                       # Configuration form
│   │
│   └── Exception/
│       └── AIAutoCompleteException.php            # Exception class
│
├── js/
│   ├── ai-complete-button.js         # Button handler
│   └── ai-field-highlighter.js       # Field highlighting
│
├── css/
│   ├── ai-complete.css               # Button & message styles
│   ├── ai-field-highlight.css        # Field highlight styles
│   └── ai-loading.css                # Loading spinner styles
│
├── config/
│   └── schema/
│       └── crm_ai.schema.yml         # Configuration schema
│
└── templates/
    └── crm-ai-field-suggestion.html.twig  # Field suggestion template
```

## Core Services

### 1. AIEntityAutoCompleteService

**Purpose**: Main orchestrator for AI completion workflow

**Key Methods**:

- `autoCompleteEntity($entity_type, $provided_fields, $nid)` - Main entry point
- `identifyEmptyFields()` - Find fields to complete
- `buildAIPrompt()` - Create structured prompt
- `validateSuggestions()` - Ensure suggestions match field types

**Flow**:

1. Receives form fields from user
2. Identifies empty, auto-completable fields
3. Builds structured prompt for LLM
4. Calls LLM provider service
5. Validates and formats suggestions
6. Returns typed suggestions

### 2. EntitySchemaService

**Purpose**: Manage entity field definitions and metadata

**Key Methods**:

- `getFieldDefinitions($bundle)` - Get all field definitions
- `getFieldMetadata($bundle, $field_name)` - Get field-specific metadata
- `getAutoCompletableFields($bundle)` - Get fields that can be auto-completed
- `getBundleOptions()` - Get available options for select fields

### 3. LLMProviderService

**Purpose**: Handle communication with external LLM providers

**Supported Providers**:

- **Mock** (testing): Returns realistic mock suggestions
- **OpenAI**: gpt-3.5-turbo, gpt-4, gpt-4-turbo
- **Anthropic**: claude-3 models

**Key Methods**:

- `callLLM($prompt, $options)` - Main API call
- `callOpenAI()` - OpenAI implementation
- `callAnthropic()` - Anthropic implementation

### 4. FieldValidatorService

**Purpose**: Validate and format field values

**Supported Types**:

- String fields (string, text, text_long, text_with_summary)
- Numeric fields (integer, decimal, float)
- List fields (list_string, list_integer)
- Entity references
- Booleans
- Timestamps

## API Endpoints

### POST /api/crm/ai/autocomplete

Main endpoint for requesting AI suggestions.

**Request**:

```json
{
  "entityType": "contact",
  "nodeId": 123,
  "fields": {
    "title": "John Smith",
    "field_company": "ABC Corp",
    "field_phone": ""
  },
  "csrf_token": "token_value"
}
```

**Response Success**:

```json
{
  "success": true,
  "suggestions": {
    "field_phone": {
      "value": "+1 (555) 123-4567",
      "confidence": 0.92,
      "field_type": "string",
      "label": "Phone"
    },
    "field_email": {
      "value": "john@example.com",
      "confidence": 0.88,
      "field_type": "string",
      "label": "Email"
    }
  },
  "timestamp": 1710123456
}
```

**Response Error**:

```json
{
  "success": false,
  "message": "AI completion failed: ...",
  "timestamp": 1710123456
}
```

### Security Features

- **CSRF Token Validation**: Validates X-CSRF-Token header
- **Permission Checking**: Requires `use crm ai autocomplete` permission
- **Rate Limiting**: 10 requests per hour per user (configurable)
- **Input Sanitization**: All values validated against field definitions
- **Content-Type Validation**: Requires application/json

### Error Handling

**HTTP Status Codes**:

- `200 OK` - Successful completion
- `400 Bad Request` - Missing/invalid parameters
- `403 Forbidden` - CSRF token invalid or permission denied
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Unexpected error

## Frontend Implementation

### JavaScript Libraries

#### 1. ai-complete-button.js

Handles user interactions:

- Button click detection
- Form data collection
- API communication
- Suggestion application
- Message display

**Key Functions**:

- `handleAIComplete()` - Main button handler
- `collectFormData()` - Gather form values
- `applySuggestions()` - Apply AI suggestions to form
- `markAsAIGenerated()` - Mark fields with AI classes

#### 2. ai-field-highlighter.js

Manages visual indicators:

- Field highlighting
- Undo buttons
- Confidence badges
- Hover tooltips

**Key Features**:

- Visual highlight with blue left border
- "AI Suggested" badge
- Clear button to remove suggestions
- Confidence score display

### CSS Styling

#### Main Classes

- `.btn-ai-complete` - Button styling
- `.crm-ai-generated` - Field background highlight
- `.crm-ai-field-highlighted` - Field container highlight
- `.ai-badge` - Suggestion badge
- `.ai-undo-btn` - Clear button
- `.crm-ai-message` - Status/error messages
- `.ai-loading-spinner` - Loading indicator

#### Features

- Gradient backgrounds
- Smooth animations
- Responsive design
- Dark mode support
- Accessibility improvements

## Configuration

### Admin Setting Location

`/admin/config/crm/ai`

### Configurable Options

1. **LLM Provider**
   - Mock (default, for testing)
   - OpenAI
   - Anthropic

2. **API Configuration**
   - API Key (securely stored)
   - Model selection
   - Temperature (0-1)

3. **System Settings**
   - Cache suggestions (enabled by default)
   - Rate limit (10 per hour)
   - Enabled entities (contact, deal, organization, activity)

### Configuration Storage

Stored in `crm_ai.settings` configuration:

```yaml
llm_provider: mock
llm_api_key: ""
llm_model: "gpt-3.5-turbo"
llm_temperature: 0.7
cache_suggestions: true
rate_limit_per_hour: 10
enabled_entities:
  - contact
  - deal
  - organization
  - activity
```

## Entity Support

### Supported Entity Types

| Entity             | Bundle       | Owner Field          | Notes             |
| ------------------ | ------------ | -------------------- | ----------------- |
| Contact/Lead       | contact      | field_owner          | Person entity     |
| Deal               | deal         | field_owner          | Sales opportunity |
| Organization       | organization | field_assigned_staff | Company/account   |
| Activity/Task/Note | activity     | field_assigned_to    | Task/event record |

### Auto-Completable Field Types

- Common fields: title, description, notes
- Text fields: string, text, text_long, text_with_summary
- Numeric: integer, decimal, float
- Lists: list_string, list_integer
- References: entity_reference
- Boolean: boolean
- Date/Time: timestamp

## Workflow

### User Journey

1. **User opens entity form** (Contact, Deal, etc.)
2. **Fills a few key fields** (Name, Company, Notes)
3. **Clicks "✨ AI Complete" button**
4. **System shows loading state** (button grayed out with spinner)
5. **API sends data to LLM**
6. **AI suggests missing fields**
7. **Form auto-fills suggestions** (with blue highlight)
8. **User sees "AI Suggested" badges**
9. **User can clear unwanted suggestions** (✕ Clear button)
10. **User reviews and saves normally**

### Technical Workflow

```
Form Submit (AI Button)
    ↓
validateRequest() → Check CSRF, permissions
    ↓
collectFormData() → Extract field values
    ↓
checkRateLimit() → Verify user hasn't exceeded limit
    ↓
AIEntityAutoCompleteService::autoCompleteEntity()
    ↓
identifyEmptyFields() → Find fields to complete
    ↓
buildAIPrompt() → Create structured prompt
    ↓
LLMProviderService::callLLM() → Call external API
    ↓
parseLLMResponse() → Extract JSON suggestions
    ↓
validateSuggestions() → Verify against field types
    ↓
Return JSON Response
    ↓
JavaScript: applySuggestions() → Fill form fields
    ↓
markAsAIGenerated() → Add visual indicators
    ↓
Display status message
```

## Customization

### Custom LLM Providers

Extend `LLMProviderService::callLLM()`:

```php
// In your custom module/code
$provider_service = \Drupal::service('crm_ai.llm_provider');

// Call custom provider
$response = $provider_service->callLLM(
  $prompt,
  [
    'provider' => 'custom',
    'endpoint' => 'https://your-api.com/complete',
    'api_key' => 'your-key',
  ]
);
```

### Custom Field Validators

Extend `FieldValidatorService`:

```php
$validator = \Drupal::service('crm_ai.field_validator');
$validated = $validator->validateAndFormat(
  'field_name',
  'suggested_value',
  $field_definition
);
```

### Custom Prompts

Modify `AIEntityAutoCompleteService::buildAIPrompt()`:

```php
// Create a custom service that extends AIEntityAutoCompleteService
protected function buildAIPrompt(...) {
  // Your custom prompt logic
  return $custom_prompt;
}
```

## Caching Strategy

### Suggestion Caching

Cache key: `crm_ai:suggestions:{entity_type}:{md5_hash}`

TTL: 1 hour (3600 seconds)

**Benefits**:

- Reduces API calls for similar data
- Improves performance
- Reduces LLM API costs

**Clear Cache**:

```bash
drush cache-clear default
```

### Rate Limit Cache

Cache key: `crm_ai:ratelimit:{uid}`

TTL: 1 hour (rolling window)

## Testing

### Manual Testing

1. Enable crm_ai module
2. Configure LLM provider (use Mock for testing)
3. Go to node form (e.g., /node/123/edit)
4. Fill a few fields
5. Click "✨ AI Complete"
6. Verify suggestions appear

### API Testing

```bash
curl -X POST http://localhost/api/crm/ai/autocomplete \
  -H "Content-Type: application/json" \
  -d '{
    "entityType": "contact",
    "fields": {
      "title": "John Smith",
      "field_company": "ABC Corp"
    }
  }'
```

### Debugging

Enable logging in `crm_ai` channel:

```php
// In your code
$logger = \Drupal::logger('crm_ai');
$logger->debug('Message', scope_array);
$logger->error('Error message', ['exception' => $e]);
```

View logs at `/admin/reports/dblog`

## Performance Tuning

### API Optimization

- Reduce prompt size by only including relevant context
- Use lower temperature for more deterministic results
- Cache suggestions aggressively
- Implement debouncing for API calls

### Database Optimization

- Use indexes on field values for faster searching
- Archive old activity logs
- Regular cache clearing

### Rate Limiting

Default: 10 requests/hour can be adjusted:

- Increase in admin settings: `/admin/config/crm/ai`
- Or programmatically: `$config->set('rate_limit_per_hour', 20)`

## Security Hardening

### API Key Management

```php
// Never hardcode API keys
// Use environment variables:
$api_key = getenv('OPENAI_API_KEY');

// Or Drupal secrets:
// Add to settings.php:
// $settings['openai_api_key'] = 'your-key';
// Access with: \Drupal::getContainer()->getParameter('openai_api_key')
```

### CSRF Protection

All endpoints validate CSRF tokens automatically via middleware.

### Permission Matrix

| Role          | Permission              | Access        |
| ------------- | ----------------------- | ------------- |
| Anonymous     | -                       | None          |
| Authenticated | use crm ai autocomplete | Can use AI    |
| Sales Manager | administer crm ai       | Can configure |
| Admin         | administer crm ai       | Full access   |

## Troubleshooting

### Issue: Button not appearing

**Check**:

- Module enabled: `drush pm-list | grep crm_ai`
- User permission: `/admin/people/permissions` search "crm ai"
- Form context: Only shows on existing entity forms

### Issue: "Mock" suggestions appearing

**Solutions**:

- Configure real API key in settings
- Check provider selection
- Verify API key format

### Issue: Undefined field errors

**Fix**:

- Verify entity bundle exists
- Check field definitions: `php -r "echo shell_exec('drush ev \"$mg = \Drupal::service(\\'entity_field.manager\\'); $fields = $mg->getFieldDefinitions(\\'node\\', \\'contact\\'); print_r(array_keys($fields));\"');"`
- Enable logging to debug

### Issue: Rate limit exceeded

**Solutions**:

- Increase limit in `/admin/config/crm/ai`
- Wait 1 hour for window to reset
- Clear cache: `drush cr`

## Performance Metrics

### Expected Performance

| Task                    | Time   |
| ----------------------- | ------ |
| API request (mock)      | <100ms |
| API request (OpenAI)    | 1-3s   |
| API request (Anthropic) | 2-5s   |
| Form submission         | <200ms |
| UI update               | <300ms |

### Optimization Tips

1. Use Mock provider for development
2. Enable suggestion caching
3. Use lower rate limits for public sites
4. Cache frequently-suggested entities
5. Use CDN for static assets

## Monitoring

### Logging

Logs go to Drupal's database log:

- View: `/admin/reports/dblog`
- Search: "crm_ai"
- Filter by severity level

### Key Log Points

- Module installation
- Configuration changes
- API calls (success/failure)
- Rate limit violations
- Validation errors

### Metrics to Monitor

- API call frequency
- Average response time
- Cache hit rate
- Rate limit hits
- Suggestion acceptance rate

## Maintenance

### Regular Tasks

1. **Monthly**: Review API usage and costs
2. **Quarterly**: Monitor suggestion quality feedback
3. **Annually**: Update LLM models and prompts

### Upgrade Path

The module is designed to be upgradeable:

- Services are loosely coupled
- Configuration is stored separately
- Database schema is minimal
- No custom tables required

## Contributing

For improvements:

1. Extend services with custom logic
2. Create alternative field validators
3. Implement custom LLM providers
4. Contribute back improvements to core

## Support & Resources

- **Module README**: [README.md](README.md)
- **API Docs**: Request/Response examples in README
- **LLM Docs**:
  - OpenAI: https://platform.openai.com/docs
  - Anthropic: https://docs.anthropic.com
- **Drupal Docs**: https://www.drupal.org/docs
- **Issues**: Create issue reports in project repo

## Conclusion

The CRM AI AutoComplete feature provides a modern, user-friendly way to complete entity data using AI. The modular architecture allows for easy customization and integration with existing CRM workflows.
