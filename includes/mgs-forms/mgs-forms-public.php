<?php
if( !class_exists('MGS_Forms_Public') ){
    class MGS_Forms_Public{
        private static $flag_post;
        private static $string_required;

        public static $plg_url;
        public $config_form;

        public function __construct(){
            self::$plg_url = MGS_THEME_UPG_PLUGIN_DIR_URL.'includes/mgs-forms/';
            $this->$config_form = [];

            add_shortcode('mgs-forms', [$this, 'render_mgs_forms']);

            add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);


            self::$flag_post = false;
            self::$string_required = ' <span class="required">*</span>';
        }


        public function render_mgs_forms($attrs){
            if( isset($attrs['id']) && is_numeric($attrs['id']) && $attrs['id']>0 ){
                $tmp = get_post_meta($attrs['id'], 'mgs-forms-fields');
                if( !$tmp ) return ('no se pudieron cargar los parametros del formulario.');

                $form_data = $tmp[0];
                $uniqid = uniqid();
                $unique_class = 'mgs-forms-'.$uniqid;
                $out = '';
                $out .= '<pre>'.print_r($form_data, true).'</pre>';
                $out .= '
                    <div id="mgs-forms-'.$attrs['id'].'" class="mgs-forms '.$unique_class.'">
                        <div class="mgs-forms-wrapper">
                            <form id="mgs-form-'.$attrs['id'].'" method="post" enctype="multipart/form-data">
                                '.$this->forms_items($form_data).'
                            </form>
                        </div>
                    </div>
                ';

                wp_enqueue_style('mgs-forms-css');
                wp_enqueue_style('mgs-forms-fa');
                wp_enqueue_style('mgs-forms-bootstrap');

                wp_localize_script('mgs-forms-js', 'mgs_form_js', [
                        'ajaxurl'			=> admin_url('admin-ajax.php'),
                        'lang_folder'		=> self::$plg_url.'assets/lang',
                        'lang'				=> 'es-ES',
                        'data'			    => json_encode($this->$config_form, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    ]
                );
                wp_enqueue_script('mgs-forms-js', ['jquery', 'mgs-validate-js']);

                return $out;
            }else{
                return 'Formulario no encontrado';
            }
        }

        private function forms_items($items){
            $out = '';
            foreach( $items as $item ){
                $this->FIX_Item($item);
                $out .= $this->_open_item_warpper($item);
                switch( $item->type ){
                    case 'text':
                        $out .= $this->_text($item);
                        break;
                    case 'textarea':
                        $out .= $this->_textarea($item);
                        break;
                    case 'select':
                        $out .= $this->_select($item);
                        break;
                    case 'checkbox-group':
                        $out .= $this->_checkbox_group($item);
                        break;
                    case 'radio-group':
                        $out .= $this->_radio_group($item);
                        break;
                    case 'number':
                        $out .= $this->_number($item);
                        break;



                    case 'button':
                        $out .= $this->_button($item);
                        break;
                }
                $out .= $this->_close_item_warpper();
            }
            return $out;
        }

        private function _number($item){
            $out = $this->_label($item);
            $out .= '<input type="number" class="'.$item->className.'" name="'.$item->name.'" id="mgs_forms_item-'.$item->name.'" placeholder="'.$item->placeholder.'" value="'. $item->value.'" data-placement="bottom"';
			if( $item->required ) $out .= ' required="required"';
			if( $item->readonly ) $out .= ' readonly';
            if( $item->min ) $out .= ' min="'.$item->min.'"';
            if( $item->max ) $out .= ' max="'.$item->max.'"';
            if( $item->step ) $out .= ' step="'.$item->step.'"';
            $out .= '/>';
            $out .= ( $item->description ) ? '<p class="help">'.$item->description.'</p>' : '';
            return $out;
        }

        private function _radio_group($item){
            $out = $this->_label($item);
            $required = ( $item->required ) ? 'required' : '';
            $inline = ($item->inline ) ? 'inline' : '';
            $fontawesome = ( $item->FontAwesomeReplace ) ? 'FontAwesomeReplace' : '';
            $out .= '<fieldset class="'.$item->className.'mgs-radio-group '.$required.'">';
            foreach( $item->values as $k=>$check ){
                $selected = ( $check->selected ) ? 'checked' : '';
                $out .= '
                    <div class="radio '.$inline.' '.$fontawesome.'">
                        <input type="radio" name="'.$item->name.'[]" id="'.$item->name.'-'.$k.'" value="'.$check->value.'" '.$selected.'>
                        <label for="'.$item->name.'-'.$k.'">'.$check->label.'</label>
                    </div>
                ';
            }
            $out .= '</fieldset>';
            $out .= ( $item->description ) ? '<p class="help">'.$item->description.'</p>' : '';
            return $out;
        }

        private function _checkbox_group($item){
            $out = $this->_label($item);
            $required = ( $item->required ) ? 'required' : '';
            $inline = ($item->inline ) ? 'inline' : '';
            $fontawesome = ( $item->FontAwesomeReplace ) ? 'FontAwesomeReplace' : '';
            $out .= '<fieldset class="'.$item->className.'mgs-check-group '.$required.'">';
            foreach( $item->values as $k=>$check ){
                $selected = ( $check->selected ) ? 'checked' : '';
                $out .= '
                    <div class="checkbox '.$inline.' '.$fontawesome.'">
                        <input type="checkbox" name="'.$item->name.'[]" id="'.$item->name.'-'.$k.'" value="'.$check->value.'" '.$selected.'>
                        <label for="'.$item->name.'-'.$k.'">'.$check->label.'</label>
                    </div>
                ';
            }
            $out .= '</fieldset>';
            $out .= ( $item->description ) ? '<p class="help">'.$item->description.'</p>' : '';
            return $out;
        }

        private function _select($item){
            $out = $this->_label($item);
            $out .= '<select class="'.$item->className.'" name="'.$item->name.'" id="mgs_forms_item-'.$item->name.'"';
			if( $item->required ) $out .= ' required="required"';
			if( $item->readonly ) $out .= ' readonly';
            $out .= '>';
            $s = '';
            foreach( $item->values as $option ){
                $s = ( $option->selected ) ? 'selected' : '';
                $out .= '<option value="'.$option->value.'" '.$s.'>'.$option->label.'</option>';
            }
            $out .= '</select>';
            $out .= ( $item->description ) ? '<p class="help">'.$item->description.'</p>' : '';
            return $out;
        }

        private function _textarea($item){
            $out = $this->_label($item);
            $out .= '<textarea class="'.$item->className.'" name="'.$item->name.'" id="mgs_forms_item-'.$item->name.'" placeholder="'.$item->placeholder.'" rows="'.$item->rows.'"';
			if( $item->required ) $out .= ' required="required"';
			if( $item->readonly ) $out .= ' readonly';
            $out .= '>'.$item->value.'</textarea>';
            $out .= ( $item->description ) ? '<p class="help">'.$item->description.'</p>' : '';
			return $out;
        }

        private function _text($item){
            $out = $this->_label($item);
            $out .= '<input type="'.$item->subtype.'" class="'.$item->className.'" name="'.$item->name.'" id="mgs_forms_item-'.$item->name.'" placeholder="'.$item->placeholder.'" value="'. $item->value.'" data-placement="bottom"';
			if( $item->required ) $out .= ' required="required"';
			if( $item->readonly ) $out .= ' readonly';
			if( $item->maxlength ) $out .= ' maxlength="'.$item->maxlength.'"';
            $out .= '/>';
            
            $out .= ( $item->description ) ? '<p class="help">'.$item->description.'</p>' : '';
            
            return $out;
        }

        private function _label($item){
            if( $item->ShowLabel ){
                $out = '<label for="mgs_forms_item-'.$item->name.'" class="mgs-forms-label form-control-label">'.$item->label.'</label>';
                return $out;
            }
        }

        private function _button($item){
            $out = '<button type="'.$item->subtype.'" class="'.$item->className.'">'.$item->label.'</button>';
            return $out;
        }

        private function FIX_Item($item){
            unset($item->access);
            
            if( $item->type=='date' ){
                if( $item->EnabledJquery ){
                    $item->type = 'text';
                    $item->subtype = 'text';
                    $this->$config_form['datapicker'][] = [
                        'id'    => $item->name,
                    ];
                }else{
                    $item->type = 'text';
                    $item->subtype = 'date';
                }
            }


            //valor
            if( self::$flag_post ){
                $item->value = $_POST[$item->name];
            }elseif( !isset($item->value) ){
                $item->value = '';
            }else{
                $item->value = $item->value;
            }
            
            //required
            if( $item->required ){
                if( isset($item->label) ) $item->label .= self::$string_required;
            }

            //class
            $class = [
                'mgs-forms-control',
                $item->className
            ];
            $item->className = implode(' ', array_unique($class));

        }

        private function _open_item_warpper($item){
            $out = '';
            $class = [
                'mgs-forms-item',
                'mgs-forms-item-'.$item->type,
                $item->ColWidth
            ];
            $class = implode(' ', $class);
            $out .= '<div class="'.$class.'">';
            return $out;
        }

        private function _close_item_warpper(){
            return '</div>';
        }

        public function enqueue_scripts(){
            wp_register_script('mgs-validate-js', self::$plg_url.'assets/js/jquery.validate.min.js', ['jquery']);
            wp_register_script('mgs-forms-js', self::$plg_url.'assets/js/public.js', ['jquery', 'mgs-validate-js']);
            
            

            wp_register_style('mgs-forms-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css');
            wp_register_style('mgs-forms-fa', 'https://use.fontawesome.com/releases/v5.15.1/css/all.css');
            wp_register_style('mgs-forms-css', self::$plg_url.'assets/css/public.css');
        }
    }
}