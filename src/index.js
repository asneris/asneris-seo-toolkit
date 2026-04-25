import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
import { PanelBody, TextControl, TextareaControl, ToggleControl, SelectControl, Button, ExternalLink, Notice } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

// Template resolver helper (mirrors PHP parse behavior for sidebar preview only)
const resolveTemplate = (template, context = {}) => {
  if (!template) {
    return '';
  }

  let output = template;
  Object.entries(context).forEach(([key, value]) => {
    output = output.split(`{${key}}`).join(value || '');
  });

  // Remove unreplaced variables and normalize spaces (same behavior as PHP parser)
  output = output.replace(/\{[^}]+\}/g, '');
  output = output.replace(/\s+/g, ' ').trim();

  return output;
};

const getResolvedTemplateByMetaKey = (metaKey, postType, context) => {
  const titleTemplates = window.asnerisseoData?.titleTemplates || {};
  const descriptionTemplates = window.asnerisseoData?.descriptionTemplates || {};

  if (metaKey === '_ASNERISSEO_title') {
    return resolveTemplate(titleTemplates[postType] || '', context);
  }

  if (metaKey === '_ASNERISSEO_description') {
    return resolveTemplate(descriptionTemplates[postType] || '', context);
  }

  return '';
};

const IndexNowSubmit = () => {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [notice, setNotice] = useState(null);
  const postId = useSelect(select => select('core/editor').getCurrentPostId());
  const postStatus = useSelect(select => select('core/editor').getEditedPostAttribute('status'));
  
  const handleSubmit = () => {
    setIsSubmitting(true);
    setNotice(null);
    
    const ajaxurl = window.asnerisseoData?.ajaxurl || window.ajaxurl || '/wp-admin/admin-ajax.php';
    const nonce = window.asnerisseoData?.indexnowNonce || window.ASNERISSEO_indexnow_nonce;
    
    fetch(ajaxurl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'ASNERISSEO_manual_indexnow',
        nonce: nonce,
        post_id: postId
      })
    })
    .then(response => response.json())
    .then(data => {
      setIsSubmitting(false);
      if (data.success) {
        setNotice({ type: 'success', message: data.data.message });
      } else {
        setNotice({ type: 'error', message: data.data.message || __('Failed to submit', 'asneris-seo-toolkit') });
      }
      setTimeout(() => setNotice(null), 5000);
    })
    .catch(error => {
      setIsSubmitting(false);
      setNotice({ type: 'error', message: __('Request failed', 'asneris-seo-toolkit') });
      setTimeout(() => setNotice(null), 5000);
    });
  };
  
  if (postStatus !== 'publish') {
    return (
      <Notice status="warning" isDismissible={false}>
        {__('Post must be published to submit to IndexNow', 'asneris-seo-toolkit')}
      </Notice>
    );
  }
  
  return (
    <>
      {notice && (
        <Notice status={notice.type} isDismissible={false} style={{ marginBottom: '12px' }}>
          {notice.message}
        </Notice>
      )}
      
      <p style={{ marginBottom: '12px', color: '#646970', fontSize: '13px' }}>
        {__('Manually notify search engines about this page update via IndexNow protocol.', 'asneris-seo-toolkit')}
      </p>
      
      <Button
        variant="secondary"
        onClick={handleSubmit}
        disabled={isSubmitting}
        style={{ width: '100%' }}
      >
        {isSubmitting ? __('Submitting...', 'asneris-seo-toolkit') : __('Submit to IndexNow', 'asneris-seo-toolkit')}
      </Button>
      
      <p style={{ marginTop: '12px', color: '#646970', fontSize: '12px', fontStyle: 'italic' }}>
        {__('Note: IndexNow must be enabled in plugin settings.', 'asneris-seo-toolkit')}
      </p>
    </>
  );
};

const MetaField = ({ label, metaKey, help, type = 'text', placeholder = '' }) => {
  const value = useSelect(
    select => select('core/editor').getEditedPostAttribute('meta')[metaKey] || '',
    [metaKey]
  );
  const { editPost } = useDispatch('core/editor');
  const [validationError, setValidationError] = useState('');
  
  // Get template preview
  const postTitle = useSelect(select => select('core/editor').getEditedPostAttribute('title'));
  const postType = useSelect(select => select('core/editor').getCurrentPostType());
  const siteName = window.asnerisseoData?.siteName || 'Site';
  const titleSeparator = window.asnerisseoData?.titleSeparator || '|';
  
  let templatePreview = null;
  if (!value && (metaKey === '_ASNERISSEO_title' || metaKey === '_ASNERISSEO_description')) {
    const context = {
      title: postTitle || '',
      site: siteName || '',
      separator: titleSeparator,
      post_type: postType || '',
      category: '',
      tag: '',
      author: '',
      date: '',
      year: '',
      month: '',
      excerpt: '',
    };

    const resolved = getResolvedTemplateByMetaKey(metaKey, postType, context);

    if (resolved) {
      templatePreview = resolved;
    } else if (metaKey === '_ASNERISSEO_description') {
      templatePreview = 'Auto: Generated from post content excerpt';
    }
  }
  
  const validateInput = (inputValue) => {
    // Clear error if empty (empty is always valid)
    if (!inputValue || inputValue.trim() === '') {
      setValidationError('');
      return true;
    }
    
    // Check for dangerous patterns first (applies to all fields)
    if (/<script|<\/script|javascript:|onerror=|onload=|<iframe|eval\(|data:text\/html/i.test(inputValue)) {
      setValidationError(__('Content contains potentially dangerous patterns', 'asneris-seo-toolkit'));
      return false;
    }
    
    // Validate URLs for canonical and OG image
    if (metaKey === '_ASNERISSEO_canonical' || metaKey === '_ASNERISSEO_og_image') {
      // Additional URL-specific security checks
      if (/script|javascript|data:|vbscript:|file:|about:/i.test(inputValue)) {
        setValidationError(__('URL contains potentially dangerous protocols or content', 'asneris-seo-toolkit'));
        return false;
      }
      
      try {
        const urlObj = new URL(inputValue);
        
        // Only allow http and https protocols
        if (!['http:', 'https:'].includes(urlObj.protocol)) {
          setValidationError(__('Only HTTP and HTTPS URLs are allowed', 'asneris-seo-toolkit'));
          return false;
        }
        
        // Additional validation for OG image
        if (metaKey === '_ASNERISSEO_og_image') {
          const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'];
          const pathname = urlObj.pathname;
          const extension = pathname.split('.').pop().toLowerCase().split('?')[0].split('#')[0];
          
          if (!allowedExtensions.includes(extension)) {
            setValidationError(__('Image URL must have a valid image extension (jpg, png, gif, webp, svg)', 'asneris-seo-toolkit'));
            return false;
          }
        }
        
        setValidationError('');
        return true;
      } catch (e) {
        const label = metaKey === '_ASNERISSEO_canonical' ? __('Canonical URL', 'asneris-seo-toolkit') : __('OG Image URL', 'asneris-seo-toolkit');
        setValidationError(__(`${label} must be a valid URL (e.g., https://example.com/page)`, 'asneris-seo-toolkit'));
        return false;
      }
    }
    
    setValidationError('');
    return true;
  };
  
  const handleChange = (inputValue) => {
    validateInput(inputValue);
    editPost({ meta: { [metaKey]: inputValue } });
  };
  
  const Component = type === 'textarea' ? TextareaControl : TextControl;
  
  return (
    <>
      <Component
        label={label}
        value={value}
        onChange={handleChange}
        help={help}
        placeholder={placeholder}
        className={validationError ? 'has-error' : ''}
      />
      {validationError && (
        <Notice status="error" isDismissible={false} style={{ margin: '-12px 0 12px 0', padding: '8px 12px' }}>
          {validationError}
        </Notice>
      )}
      {templatePreview && !validationError && (
        <div style={{ 
          fontSize: '12px', 
          color: '#646970', 
          marginTop: '-12px', 
          marginBottom: '12px',
          padding: '8px 10px',
          background: '#fff3cd',
          border: '1px solid #ffc107',
          borderRadius: '4px',
          fontStyle: 'italic'
        }}>
          <strong style={{ color: '#d63638', fontWeight: '600' }}>Auto:</strong> {templatePreview}
        </div>
      )}
    </>
  );
};

const RobotsControl = ({ metaKey, label, options }) => {
  const value = useSelect(
    select => select('core/editor').getEditedPostAttribute('meta')[metaKey] || options[0].value,
    [metaKey]
  );
  const { editPost } = useDispatch('core/editor');
  
  return (
    <SelectControl
      label={label}
      value={value}
      options={options}
      onChange={(v) => editPost({ meta: { [metaKey]: v } })}
    />
  );
};

const CharacterCount = ({ text, maxLength, optimal }) => {
  const length = text ? text.length : 0;
  let color = '#46b450'; // green
  
  if (length === 0) {
    color = '#646970'; // gray
  } else if (length < optimal.min || length > optimal.max) {
    color = '#dba617'; // warning
  } else if (length > maxLength) {
    color = '#d63638'; // error
  }
  
  return (
    <div style={{ 
      fontSize: '12px', 
      color: color, 
      marginTop: '-8px', 
      marginBottom: '12px',
      fontWeight: '500'
    }}>
      {length} / {maxLength} characters {optimal && `(optimal: ${optimal.min}-${optimal.max})`}
    </div>
  );
};

const SEOScore = () => {
  const meta = useSelect(select => select('core/editor').getEditedPostAttribute('meta'));
  const title = useSelect(select => select('core/editor').getEditedPostAttribute('title'));
  const postTitle = useSelect(select => select('core/editor').getEditedPostAttribute('title'));
  const postType = useSelect(select => select('core/editor').getCurrentPostType());
  const siteName = window.asnerisseoData?.siteName || 'Site';
  const titleSeparator = window.asnerisseoData?.titleSeparator || '|';
  
  const [score, setScore] = useState(0);
  const [suggestions, setSuggestions] = useState([]);
  
  const calculateScore = () => {
    let points = 0;
    const tips = [];
    
    const context = {
      title: postTitle || '',
      site: siteName || '',
      separator: titleSeparator,
      post_type: postType || '',
      category: '',
      tag: '',
      author: '',
      date: '',
      year: '',
      month: '',
      excerpt: '',
    };

    const templateTitle = getResolvedTemplateByMetaKey('_ASNERISSEO_title', postType, context);
    const templateDesc = getResolvedTemplateByMetaKey('_ASNERISSEO_description', postType, context);

    // Title check - factor in actual configured title template if no manual override
    const effectiveTitle = meta._ASNERISSEO_title || templateTitle || title;
    if (effectiveTitle && effectiveTitle.length >= 30 && effectiveTitle.length <= 60) {
      points += 20;
    } else if (!effectiveTitle || effectiveTitle.length < 30) {
      tips.push('SEO title should be 30-60 characters');
    } else if (effectiveTitle.length > 60) {
      tips.push('SEO title is too long (max 60 characters)');
    }
    
    // Description check - factor in actual configured description template if no manual override
    const effectiveDesc = meta._ASNERISSEO_description || templateDesc;
    if (effectiveDesc && effectiveDesc.length >= 120 && effectiveDesc.length <= 160) {
      points += 20;
    } else if (!effectiveDesc) {
      // Even without manual override, description is auto-generated
      points += 10; // Partial credit for auto-generated
      tips.push('Add a custom meta description for better results (120-160 characters)');
    } else if (effectiveDesc.length < 120) {
      tips.push('Meta description is too short (min 120 characters)');
    } else if (effectiveDesc.length > 160) {
      tips.push('Meta description is too long (max 160 characters)');
    }
    
    // Canonical URL check
    if (meta._ASNERISSEO_canonical) {
      points += 15;
    }
    
    // OG Title check - uses SEO title as fallback
    if (meta._ASNERISSEO_og_title || meta._ASNERISSEO_title) {
      points += 15;
    } else {
      tips.push('Add an Open Graph title for better social sharing');
    }
    
    // OG Description check - uses meta description as fallback
    if (meta._ASNERISSEO_og_description || meta._ASNERISSEO_description) {
      points += 15;
    }
    
    // OG Image check
    if (meta._ASNERISSEO_og_image) {
      points += 15;
    } else {
      tips.push('Add an Open Graph image for social media previews');
    }
    
    setScore(points);
    setSuggestions(tips);
  };
  
  // Recalculate on meta changes
  useEffect(() => {
    calculateScore();
  }, [meta]);
  
  const getScoreColor = () => {
    if (score >= 80) return '#46b450';
    if (score >= 50) return '#dba617';
    return '#d63638';
  };
  
  return (
    <div style={{ 
      padding: '16px', 
      background: '#f6f7f7', 
      borderRadius: '4px',
      marginBottom: '16px'
    }}>
      <div style={{ 
        display: 'flex', 
        alignItems: 'center', 
        justifyContent: 'space-between',
        marginBottom: '8px'
      }}>
        <strong>SEO Score</strong>
        <span style={{ 
          fontSize: '24px', 
          fontWeight: 'bold',
          color: getScoreColor()
        }}>
          {score}%
        </span>
      </div>
      
      <div style={{ 
        height: '8px', 
        background: '#ddd', 
        borderRadius: '4px',
        overflow: 'hidden'
      }}>
        <div style={{ 
          height: '100%', 
          width: score + '%',
          background: getScoreColor(),
          transition: 'width 0.3s ease'
        }} />
      </div>
      
      {suggestions.length > 0 && (
        <div style={{ marginTop: '12px' }}>
          <strong style={{ fontSize: '12px', color: '#646970' }}>Suggestions:</strong>
          <ul style={{ 
            margin: '8px 0 0 0', 
            padding: '0 0 0 20px',
            fontSize: '12px',
            color: '#646970'
          }}>
            {suggestions.map((tip, index) => (
              <li key={index}>{tip}</li>
            ))}
          </ul>
        </div>
      )}
      
      <Button 
        variant="secondary"
        __next40pxDefaultSize
        onClick={calculateScore}
        style={{ marginTop: '12px', width: '100%' }}
      >
        Recalculate Score
      </Button>
    </div>
  );
};

registerPlugin('asneris-seo-sidebar', {
  render() {
    const schemaEnabled = useSelect(select =>
      select('core/editor').getEditedPostAttribute('meta')._ASNERISSEO_schema_enabled
    );
    const { editPost } = useDispatch('core/editor');
    const { openGeneralSidebar } = useDispatch('core/edit-post');
    const { createNotice } = useDispatch('core/notices');
    
    // Monitor save errors
    const saveError = useSelect(select => {
      const editor = select('core/editor');
      return editor.getLastEntitySaveError?.('postType', editor.getCurrentPostType(), editor.getCurrentPostId());
    });
    
    useEffect(() => {
      if (saveError && saveError.message) {
        // Check if it's a meta validation error
        if (saveError.message.includes('meta') || saveError.message.includes('WP_Error')) {
          createNotice(
            'error',
            __('Some SEO fields contain invalid data and were not saved. Please check the error messages above each field.', 'asneris-seo-toolkit'),
            {
              type: 'snackbar',
              isDismissible: true,
            }
          );
        }
      }
    }, [saveError, createNotice]);
    
    // Auto-open sidebar when accessed via ?asneris-seo-open=1
    useEffect(() => {
      const shouldOpen = sessionStorage.getItem('asneris-seo-open');
      
      if (shouldOpen !== '1') {
        return;
      }
      
      // Clear the flag so it only opens once
      sessionStorage.removeItem('asneris-seo-open');
      
      // Attempt to open the sidebar
      setTimeout(() => {
        if (openGeneralSidebar) {
          openGeneralSidebar('asneris-seo-sidebar/asneris-seo-sidebar');
        }
      }, 100);
    }, [openGeneralSidebar]);
    
    return (
      <>
        <PluginSidebarMoreMenuItem target="asneris-seo-sidebar">
          {__('Asneris SEO Toolkit', 'asneris-seo-toolkit')}
        </PluginSidebarMoreMenuItem>
        
        <PluginSidebar
          name="asneris-seo-sidebar"
          title={__('Asneris SEO Toolkit', 'asneris-seo-toolkit')}
          icon="search"
        >
          <PanelBody 
            title={__('SEO Overview', 'asneris-seo-toolkit')} 
            initialOpen={true}
          >
            <SEOScore />
            <ExternalLink href="/wp-admin/admin.php?page=asneris-seo">
              {__('Open SEO Settings', 'asneris-seo-toolkit')}
            </ExternalLink>
          </PanelBody>
          
          <PanelBody 
            title={__('Search Appearance', 'asneris-seo-toolkit')} 
            initialOpen={true}
          >
            <MetaField 
              label={__('SEO Title', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_title" 
              placeholder="Custom title for search engines"
              help="Leave empty to use Templates tab title template (fallback: post title)"
            />
            <CharacterCount 
              text={useSelect(select => 
                select('core/editor').getEditedPostAttribute('meta')._ASNERISSEO_title
              )}
              maxLength={60}
              optimal={{ min: 30, max: 60 }}
            />
            
            <MetaField 
              label={__('Meta Description', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_description"
              type="textarea"
              placeholder="Brief description of your content"
              help="Leave empty to use Templates tab description template (fallback: excerpt/content)"
            />
            <CharacterCount 
              text={useSelect(select => 
                select('core/editor').getEditedPostAttribute('meta')._ASNERISSEO_description
              )}
              maxLength={160}
              optimal={{ min: 120, max: 160 }}
            />
            
            <MetaField 
              label={__('Canonical URL', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_canonical"
              placeholder="https://example.com/canonical-url"
              help="Leave empty to use the current URL"
            />
          </PanelBody>
          
          <PanelBody 
            title={__('Robots Meta', 'asneris-seo-toolkit')} 
            initialOpen={false}
          >
            <RobotsControl 
              label={__('Index', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_robots_index"
              options={[
                { label: 'Index (allow search engines)', value: 'index' },
                { label: 'No Index (hide from search)', value: 'noindex' }
              ]}
            />
            
            <RobotsControl 
              label={__('Follow', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_robots_follow"
              options={[
                { label: 'Follow (allow link following)', value: 'follow' },
                { label: 'No Follow (prevent link following)', value: 'nofollow' }
              ]}
            />
          </PanelBody>
          
          <PanelBody 
            title={__('Social Media (Open Graph)', 'asneris-seo-toolkit')} 
            initialOpen={false}
          >
            <MetaField 
              label={__('Social Title', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_og_title"
              placeholder="Title for social media"
              help="Leave empty to use SEO title"
            />
            
            <MetaField 
              label={__('Social Description', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_og_description"
              type="textarea"
              placeholder="Description for social media"
              help="Leave empty to use meta description"
            />
            
            <MetaField 
              label={__('Social Image URL', 'asneris-seo-toolkit')}
              metaKey="_ASNERISSEO_og_image"
              placeholder="https://example.com/image.jpg"
              help="Recommended: 1200x630px"
            />
          </PanelBody>
          
          <PanelBody 
            title={__('Schema (Structured Data)', 'asneris-seo-toolkit')} 
            initialOpen={false}
          >
            <ToggleControl
              label={__('Enable Schema', 'asneris-seo-toolkit')}
              checked={schemaEnabled}
              onChange={(v) => editPost({ meta: { _ASNERISSEO_schema_enabled: v } })}
              help="Adds structured data markup for better search results"
            />
            
            {schemaEnabled && (
              <RobotsControl
                label={__('Schema Type', 'asneris-seo-toolkit')}
                metaKey="_ASNERISSEO_schema_type"
                options={[
                  { label: 'Auto-detect (recommended)', value: '' },
                  { label: 'Article / Blog Post', value: 'Article' },
                  { label: 'News Article', value: 'NewsArticle' },
                  { label: 'Blog Posting', value: 'BlogPosting' },
                  { label: 'Web Page', value: 'WebPage' },
                  { label: 'Product', value: 'Product' },
                  { label: 'Event', value: 'Event' },
                  { label: 'Course', value: 'Course' },
                  { label: 'Recipe', value: 'Recipe' },
                  { label: 'Video', value: 'VideoObject' },
                  { label: 'FAQ Page', value: 'FAQPage' },
                  { label: 'How-To', value: 'HowTo' },
                  { label: 'Job Posting', value: 'JobPosting' },
                  { label: 'Service', value: 'Service' },
                ]}
              />
            )}
          </PanelBody>

          <PanelBody 
            title={__('IndexNow', 'asneris-seo-toolkit')} 
            initialOpen={false}
          >
            <IndexNowSubmit />
          </PanelBody>
        </PluginSidebar>
      </>
    );
  }
});
