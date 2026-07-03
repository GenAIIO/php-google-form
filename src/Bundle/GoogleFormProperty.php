<?php

namespace GenAI\GoogleForm\Bundle;

use GenAI\Property\AbstractProperty;
use GenAI\Property\Attribute\Property;
use GenAI\Property\Util\Map;

/**
 * The Google Form id, from the [googleform] group of the app config (optional —
 * empty means submissions are skipped):
 *
 *   [googleform]
 *   form_id = "1FAIpQLSf..."
 *
 * The id is the part of the form URL ...\/forms\/d\/e\/<form_id>\/viewform. The
 * field map (your fields -> entry.NNN ids) is form-specific and lives in the
 * app's GoogleForm bean, not here.
 *
 * Runtime class (PHP 5.3-safe).
 */
#[Property(group: 'googleform', optional: true)]
class GoogleFormProperty extends AbstractProperty
{
    private $formId;

    public function bindData(Map $data)
    {
        $this->formId = $data->get('form_id');
    }

    public function getFormId()
    {
        return $this->formId !== null ? $this->formId : '';
    }
}
