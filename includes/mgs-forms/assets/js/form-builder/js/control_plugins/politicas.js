if( !window.fbControls ) window.fbControls = [];

window.fbControls.push(function(controlClass){

    class controlPoliticas extends controlClass{
        static get definition(){
            return {
                icon    : '',
                i18n    : {
                    default : 'Politicas',
                },
            }
        }

        build(){
            delete this.config['description']
            delete this.config['placeholder']
            const { values, value, placeholder, type, inline, other, toggle, ...data } = this.config
            data['type'] = 'checkbox';
            data['selected'] = true;
            data['value'] = 'si';
            console.log(value, placeholder, type, inline, other, toggle, data);
            this.dom = this.markup('input', [], data)
            return this.dom;

            //return this.markup('div');
        };

    };

    controlClass.register('politicas', controlPoliticas);
    return controlPoliticas;
})