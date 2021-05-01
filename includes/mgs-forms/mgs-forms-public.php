<?php
if( !class_exists('MGS_Forms_Public') ){
    class MGS_Forms_Public{
        private static $flag_post;
        private static $string_required;

        public static $countrys;
        public static $plg_url;
        public $config_form;

        public function __construct(){
            self::$plg_url = MGS_THEME_UPG_PLUGIN_DIR_URL.'includes/mgs-forms/';
            $this->$config_form = [];

            add_shortcode('mgs-forms', [$this, 'render_mgs_forms']);

            add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);


            self::$flag_post = false;
            self::$string_required = ' <span class="required">*</span>';

            self::$countrys = $this->GetCountrysList();
        }


        public function render_mgs_forms($attrs){
            if( isset($attrs['id']) && is_numeric($attrs['id']) && $attrs['id']>0 ){
                $tmp = get_post_meta($attrs['id'], 'mgs-forms-fields');
                if( !$tmp ) return ('no se pudieron cargar los parametros del formulario.');

                $form_data = $tmp[0];
                $uniqid = uniqid();
                $unique_class = 'mgs-forms-'.$uniqid;
                $out = '';
                $out .= '
                    <div id="mgs-forms-'.$attrs['id'].'" class="mgs-forms '.$unique_class.'">
                        <div class="mgs-forms-wrapper mgs-bootstrap">
                            <div class="container">
                                <form id="mgs-form-'.$attrs['id'].'" method="post" enctype="multipart/form-data">
                                    <div class="row">
                                        '.$this->forms_items($form_data).'
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                ';

                wp_enqueue_script('mgs-feather-js');

                wp_enqueue_style('mgs-forms-bootstrap');
                wp_enqueue_style('mgs-forms-css');
                

                wp_localize_script('mgs-forms-js', 'mgs_form_js', [
                        'ajaxurl'			=> admin_url('admin-ajax.php'),
                        'lang_folder'		=> self::$plg_url.'assets/lang',
                        'lang'				=> 'es-ES',
                        'data'			    => json_encode($this->$config_form, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    ]
                );
                wp_enqueue_script('mgs-forms-js', ['jquery', 'mgs-validate-js']);

                if( is_array($this->$config_form['datepicker']) && count($this->$config_form['datepicker'])>0 ){
                    wp_enqueue_script('mgs-datepicker-js');
                    wp_enqueue_style('mgs-datepicker-css');
                }

                $out .= '<pre>'.print_r($form_data, true).'</pre>';
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
                    case 'politicas':
                        $out .= $this->_politicas($item);
                        break;


                    case 'button':
                        $out .= $this->_button($item);
                        break;
                }
                $out .= $this->_close_item_warpper();
            }
            return $out;
        }

        private function _politicas($item){
            $required = ( $item->required ) ? 'required' : '';
            $out = $this->_label($item);
            $text = $item->PoliticasText;
            $textLink = $item->PoliticasLinkText;
            $page = get_privacy_policy_url();
            $link = '<a href="'.$page.'" title="'.$textLink.'">'.$textLink.'</a>';

            $text = str_replace('{{link_politicas}}', $link, $text);
            $out .= '
                <div class="mgs-checks-grp">
                    <div class=" form-check">
                        <input class="form-check-input" type="checkbox" name="'.$item->name.'" id="'.$item->name.'" value="'.$item->value.'" '.$required.'>
                        <label class="form-check-label" for="'.$item->name.'">'.$text.'</label>
                    </div>
                </div>
            ';
            return $out;
        }

        private function _number($item){
            $out = '';
            if( !$item->FloatLabel) $out .= $this->_label($item);

            $out .= '<input type="number" class="'.$item->className.'" name="'.$item->name.'" id="mgs_forms_item-'.$item->name.'" placeholder="'.$item->placeholder.'" value="'. $item->value.'" data-placement="bottom"';
			if( $item->required ) $out .= ' required="required"';
			if( $item->readonly ) $out .= ' readonly';
            if( $item->min ) $out .= ' min="'.$item->min.'"';
            if( $item->max ) $out .= ' max="'.$item->max.'"';
            if( $item->step ) $out .= ' step="'.$item->step.'"';
            $out .= '/>';

            if( $item->FloatLabel) $out .= $this->_label($item);

            $out .= ( $item->description ) ? '<p class="help">'.$item->description.'</p>' : '';
            return $out;
        }

        private function _radio_group($item){
            $out = $this->_label($item);
            $required = ( $item->required ) ? 'required' : '';
            $inline = ($item->inline ) ? 'form-check-inline' : '';

            $out .= '<div class="mgs-checks-grp">';
            foreach( $item->values as $k=>$check ){
                $selected = ( $check->selected ) ? 'checked' : '';
                $out .= '
                    <div class=" form-check '.$inline.'">
                        <input class="form-check-input" type="radio" name="'.$item->name.'" id="'.$item->name.'-'.$k.'" value="'.$check->value.'" '.$selected.'>
                        <label class="form-check-label" for="'.$item->name.'-'.$k.'">'.$check->label.'</label>
                    </div>
                ';
            }
            $out .= '</div>';
            return $out;
        }

        private function _checkbox_group($item){
            $out = $this->_label($item);
            $required = ( $item->required ) ? 'required' : '';
            $inline = ($item->inline ) ? 'form-check-inline' : '';
            $switch = ($item->SwitchesStyle ) ? 'form-switch' : '';


            $out .= '<div class="mgs-checks-grp">';
            foreach( $item->values as $k=>$check ){
                $selected = ( $check->selected ) ? 'checked' : '';
                $out .= '
                    <div class=" form-check '.$inline.' '.$switch.'">
                        <input class="form-check-input" type="checkbox" name="'.$item->name.'[]" id="'.$item->name.'-'.$k.'" value="'.$check->value.'" '.$selected.'>
                        <label class="form-check-label" for="'.$item->name.'-'.$k.'">'.$check->label.'</label>
                    </div>
                ';
            }
            $out .= '</div>';
            return $out;
        }

        private function _select($item){
            $out = '';
            if( !$item->FloatLabel) $out .= $this->_label($item);

            $out .= '<select class="'.$item->className.'" name="'.$item->name.'" id="mgs_forms_item-'.$item->name.'"';
			if( $item->required ) $out .= ' required="required"';
			if( $item->readonly ) $out .= ' readonly';
            $out .= '>';
            $s = '';
            if( $item->SelectFild=='' ){
                foreach( $item->values as $option ){
                    $s = ( $option->selected ) ? 'selected' : '';
                    $out .= '<option value="'.$option->value.'" '.$s.'>'.$option->label.'</option>';
                }
            }else{
                $data = $this->GetSelectData($item);
                foreach( $data as $k=>$v ){
                    $s = ( $item->value==$k ) ? 'selected' : '';
                    $out .= '<option value="'.$k.'" '.$s.'>'.$v.'</option>';
                }
            }
            $out .= '</select>';
            $out .= '<i data-feather="chevron-down" class="ico-js ico-select-js" stroke-width="1"></i>';

            if( $item->FloatLabel) $out .= $this->_label($item);

            $out .= ( $item->description ) ? '<p class="help">'.$item->description.'</p>' : '';
            return $out;
        }

        private function _textarea($item){
            $out = '';
            if( !$item->FloatLabel) $out .= $this->_label($item);

            $out .= '<textarea class="'.$item->className.'" name="'.$item->name.'" id="mgs_forms_item-'.$item->name.'" placeholder="'.$item->placeholder.'" rows="'.$item->rows.'"';
			if( $item->required ) $out .= ' required="required"';
			if( $item->readonly ) $out .= ' readonly';
            $out .= '>'.$item->value.'</textarea>';

            if( $item->FloatLabel) $out .= $this->_label($item);

            $out .= ( $item->description ) ? '<p class="help">'.$item->description.'</p>' : '';
			return $out;
        }

        private function _text($item){
            $out = '';
            if( !$item->FloatLabel) $out .= $this->_label($item);

            $data_attr = '';
            if( $item->data_attr ){
                foreach( $item->data_attr as $k=>$v ){
                    $data_attr .= 'data-'.$k.'="'.$v.'" ';
                }
            }

            $out .= '<input '.$data_attr.' type="'.$item->subtype.'" class="'.$item->className.'" name="'.$item->name.'" id="mgs_forms_item-'.$item->name.'" placeholder="'.$item->placeholder.'" value="'. $item->value.'" data-placement="bottom"';
			if( $item->required ) $out .= ' required="required"';
			if( $item->readonly ) $out .= ' readonly';
			if( $item->maxlength ) $out .= ' maxlength="'.$item->maxlength.'"';
            $out .= '/>';
            
            if( $item->ShowDateIcon ) $out .= '<i data-feather="calendar" class="ico-js ico-date-js" stroke-width="1"></i>';

            if( $item->FloatLabel) $out .= $this->_label($item);

            $out .= ( $item->description ) ? '<p class="help">'.$item->description.'</p>' : '';
            
            return $out;
        }

        private function _label($item){
            $c= 'mgs-forms-label form-label ';
            if( !$item->ShowLabel && !$item->FloatLabel ) $c .= 'visually-hidden ';
            if( $item->FloatLabel ) $c .= 'mgs-forms-label-floted ';
            $out = '<label for="mgs_forms_item-'.$item->name.'" class="'.$c.'">'.$item->label.'</label>';
            return $out;
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
                    $item->data_attr = [
                        'datepicker' => ''
                    ];
                    $item->ShowDateIcon = true;

                    $this->$config_form['scripts']['mgs-datepicker-js'] = true;
                    $this->$config_form['datepicker'][] = [
                        'id'        => $item->name,
                        'format'    => $item->FormatsDates
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
            
            //placeholder
            if( $item->FloatLabel && $item->placeholder=='' ){
                $item->placeholder = $item->label;
            }

            //required
            if( $item->required ){
                if( isset($item->label) ) $item->label .= self::$string_required;
            }

            

            //class
            $class = [
                'mgs-forms-control',
                'forms-control',
                $item->className
            ];
            $item->className = implode(' ', array_unique($class));

        }

        private function _open_item_warpper($item){
            $out = '';
            $class = [
                'mgs-forms-item',
                'mgs-forms-item-'.$item->type,
                $item->ColWidth,
                'mb-3'
            ];
            if( $item->FloatLabel) $class[] = 'form-floating';

            $class = implode(' ', $class);
            $out .= '<div id="mgs_forms_item_wrap-'.$item->name.'" class="'.$class.'">';
            return $out;
        }

        private function _close_item_warpper(){
            return '</div>';
        }

        private function GetSelectData($item){
            if( $item->SelectFild=='paises' ){
                return self::$countrys;
            }
        }

        private function GetCountrysList(){
            $content = file_get_contents(self::$plg_url.'assets/json/countrys.json');
            $content = (array) json_decode($content);
            asort($content);
            return $content;
        }

        public function enqueue_scripts(){
            wp_register_script('mgs-validate-js', self::$plg_url.'assets/js/jquery.validate.min.js', ['jquery']);
            wp_register_script('mgs-datepicker-js', self::$plg_url.'assets/js/datepicker/datepicker.js', ['jquery']);
            wp_register_script('mgs-forms-js', self::$plg_url.'assets/js/public.js', ['jquery', 'mgs-validate-js']);
            wp_register_script('mgs-feather-js', 'https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js');
            
            
            

            wp_register_style('mgs-forms-bootstrap', self::$plg_url.'assets/css/mgs-bootstrap.css');
            wp_register_style('mgs-datepicker-css', self::$plg_url.'assets/js/datepicker/datepicker.css');

            wp_register_style('mgs-forms-css', self::$plg_url.'assets/css/public.css');
        }
    }
}