/*Памятки для себя:

  - декораторы накатывать только после объявления всех методов (при декорировании копируется тот метод, который объявлен на момент декорирования)
  - примеры в каком случае какие декораторы применяются в файле defaults.js оригинального select2
  - нельзя приравнивать новый класс к базовому как в этих примерах:
    https://bojanveljanovski.com/posts/extending-select2-with-adapters/
    https://bojanv91.github.io/posts/2017/10/extending-select2-with-adapters-and-decorators
    т.к. это же объекты, при приравнивании меняется ссылка и работая с переопределением методов потомка, ты на самом деле начинаешь работать с предком
    делай так:
        function QtippedMultipleSelectionAdapter() {MultipleSelection.__super__.constructor.apply(this, arguments);};
        Utils.Extend(QtippedMultipleSelectionAdapter, MultipleSelection);

 в целом декораторы работают так, он
   - обходит методы декоратора, смотрит есть ли такие методы у базового класса
   - если есть, то он их "запоминает в себя"
   - прописывает базовому методу свои методы  {
     - вызов запомненного базового метода (если такой метод был у базового метода)
     - вызов одноименного же метода декоратора
   }

 поэтому порядок вызова (базовый или метод декоратора) зависит от очередности декорирования.

*/


jQuery.fn.select2.amd.define("CountChoicesSelectionAdapter", [
        "select2/utils",
        "select2/selection/multiple",
        "select2/selection/placeholder",
        "select2/selection/eventRelay",
        "select2/selection/single",
        "select2/selection/allowClear",
    ],
    function(Utils, MultipleSelection, Placeholder, EventRelay, SingleSelection, AllowClear) {

        function CountChoicesSelectionAdapter() {MultipleSelection.__super__.constructor.apply(this, arguments);}
        Utils.Extend(CountChoicesSelectionAdapter, MultipleSelection);

        //подменяем рендер на Сингловый (в то время как вообще вся компонента - мултипл)
        CountChoicesSelectionAdapter.prototype.render = function() {
            return SingleSelection.prototype.render.call(this);
        }

        CountChoicesSelectionAdapter.prototype.update = function(data) {
            //находим отрендеренный элемент
            let $rendered = this.$selection.find('.select2-selection__rendered');
            let noItemsSelected = data.length === 0;
            let text = "";
            let formatted = "";

            //если ничего
            if (noItemsSelected) {
                //подменяем текст на заглушку или пусто
                formatted = text = this.options.get("placeholder") || "";
                //убираем класс который делает отступ справа под крестик очистки
                //this.$selection.removeClass('select2-selection--multiple-arms');
            } else {
                //для рендера
                formatted = '<span style="padding-top:4px">'+data.length + ' поз.</span>';
                //для тултипа
                text = data.length + ' позиций выбрано';
                //добавляем класс с отступом справа, чтобы крестик не накладывался на тест
                //this.$selection.addClass('select2-selection--multiple-arms');
            }

            $rendered.empty().append(formatted);
            $rendered.prop('title', text);
        };

        CountChoicesSelectionAdapter = Utils.Decorate(CountChoicesSelectionAdapter, Placeholder);
        CountChoicesSelectionAdapter = Utils.Decorate(CountChoicesSelectionAdapter, AllowClear);
        CountChoicesSelectionAdapter = Utils.Decorate(CountChoicesSelectionAdapter, EventRelay);

        return CountChoicesSelectionAdapter;
    });



jQuery.fn.select2.amd.define("QtippedMultipleSelectionAdapter", [
        "select2/utils",
        "select2/selection/multiple",
        "select2/selection/placeholder",
        "select2/selection/eventRelay",
        "select2/selection/search",
        "select2/selection/allowClear",
    ],
    function(Utils, MultipleSelection, Placeholder, EventRelay, SelectionSearch, AllowClear) {

        function QtippedMultipleSelectionAdapter() {
            MultipleSelection.__super__.constructor.apply(this, arguments);
        }

        Utils.Extend(QtippedMultipleSelectionAdapter, MultipleSelection);

        QtippedMultipleSelectionAdapter.prototype.update = function(data) {
            // copy and modify SingleSelection adapter
            //this.clear();

            MultipleSelection.prototype.update.call(this,data);
            //let $selection=this.selectionContainer();
            let $rendered = this.$selection.find('.select2-selection__rendered');
            $rendered.find('li').each(function(i,item){
                let qtipped=jQuery(item).find('span[qtip_ajxhrf]');
                if (qtipped.length) {
                    let $qtipped=jQuery(qtipped[0]);
                    let href=$qtipped.attr('qtip_ajxhrf');
                    $qtipped.removeAttr('qtip_ajxhrf');
                    if ($qtipped.hasClass('tooltipstered')) $qtipped.tooltipster('destroy');
                    jQuery(item).attr('qtip_ajxhrf',href);
                    jQuery(item).attr('title','')

                }
            })
        };

        QtippedMultipleSelectionAdapter = Utils.Decorate(QtippedMultipleSelectionAdapter, AllowClear);
        QtippedMultipleSelectionAdapter = Utils.Decorate(QtippedMultipleSelectionAdapter, Placeholder);
        QtippedMultipleSelectionAdapter = Utils.Decorate(QtippedMultipleSelectionAdapter, SelectionSearch);
        QtippedMultipleSelectionAdapter = Utils.Decorate(QtippedMultipleSelectionAdapter, EventRelay);

        return QtippedMultipleSelectionAdapter;
    });

jQuery.fn.select2.amd.define("QtippedSingleSelectionAdapter", [
        "select2/utils",
        "select2/selection/single",
        "select2/selection/placeholder",
        "select2/selection/eventRelay",
        "select2/selection/search",
        "select2/selection/allowClear",
    ],
    function(Utils, SingleSelection, Placeholder, EventRelay, SelectionSearch, AllowClear) {

        function QtippedSingleSelectionAdapter() {
            SingleSelection.__super__.constructor.apply(this, arguments);
        }

        //создаем свой селект на основе сингла
        Utils.Extend(QtippedSingleSelectionAdapter,SingleSelection);

        QtippedSingleSelectionAdapter.prototype.update = function(data) {
            // copy and modify SingleSelection adapter
            //this.clear();

            SingleSelection.prototype.update.call(this,data);
            //let $selection=this.selectionContainer();
            let item = this.$selection.find('.select2-selection__rendered');
            if (item.length) {
                let $item=jQuery(item[0]);
                //если внутри выбранного элемента у нас есть нормальная Ajax подсказка то убираем title
                if ($item.find('span[qtip_ajxhrf]').length) $item.attr('title','');
            }
            this.trigger('update',data);

        }

        //QtippedSingleSelectionAdapter = Utils.Decorate(QtippedSingleSelectionAdapter, AllowClear);

        //добавляем очистку
        QtippedSingleSelectionAdapter = Utils.Decorate(QtippedSingleSelectionAdapter, AllowClear);
        //холдер
        QtippedSingleSelectionAdapter = Utils.Decorate(QtippedSingleSelectionAdapter, Placeholder);
        //события
        QtippedSingleSelectionAdapter = Utils.Decorate(QtippedSingleSelectionAdapter, EventRelay);

        return QtippedSingleSelectionAdapter;
    });
