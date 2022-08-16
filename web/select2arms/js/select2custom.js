


//https://bojanv91.github.io/posts/2017/10/extending-select2-with-adapters-and-decorators
$.fn.select2.amd.define("ArmsSelectionAdapter", [
        "select2/utils",
        "select2/selection/multiple",
        "select2/selection/placeholder",
        "select2/selection/eventRelay",
        "select2/selection/single",
        "select2/selection/allowClear",
    ],
    function(Utils, MultipleSelection, Placeholder, EventRelay, SingleSelection, AllowClear) {

        function ArmsSelectionAdapter() {};
        // Decorates MultipleSelection with Placeholder
        ArmsSelectionAdapter=MultipleSelection
        ArmsSelectionAdapter = Utils.Decorate(ArmsSelectionAdapter, Placeholder);
        //adapter = Utils.Decorate(adapter, AllowClear);
        // Decorates adapter with EventRelay - ensures events will continue to fire
        // e.g. selected, changed

        ArmsSelectionAdapter.prototype.render = function() {
            // Use selection-box from SingleSelection adapter
            // This implementation overrides the default implementation
            let $selection = SingleSelection.prototype.render.call(this);
            return $selection;
        };

        ArmsSelectionAdapter.prototype.update = function(data) {
            // copy and modify SingleSelection adapter
            //this.clear();

            let $rendered = this.$selection.find('.select2-selection__rendered');
            let noItemsSelected = data.length === 0;
            let text = "";
            let formatted = "";

            if (noItemsSelected) {
                formatted = text = this.options.get("placeholder") || "";
                this.$selection.removeClass('select2-selection--multiple-arms');
            } else {
                formatted = '<span style="padding-top:4px">'+data.length + ' поз.</span>';
                text = data.length + ' позиций выбрано';
                this.$selection.addClass('select2-selection--multiple-arms');
            }

            $rendered.empty().append(formatted);
            $rendered.prop('title', text);
        };

        ArmsSelectionAdapter = Utils.Decorate(ArmsSelectionAdapter, AllowClear);
        ArmsSelectionAdapter = Utils.Decorate(ArmsSelectionAdapter, EventRelay);

        return ArmsSelectionAdapter;
    });

/*
$.fn.select2.amd.define("ArmsDropdownAdapter", [
        "select2/utils",
        "select2/dropdown",
        "select2/dropdown/attachBody",
        "select2/dropdown/attachContainer",
        "select2/selection/allowClear",
        //"select2/dropdown/minimumResultsForSearch"
    ],
    function(Utils, Dropdown, AttachBody, AttachContainer, allowClear) {

        // Decorate Dropdown with Search functionalities
        let dropdownWithClear = Utils.Decorate(Dropdown,allowClear);
        dropdownWithClear.prototype.render = function() {
            // Copy and modify default search render method
            let $rendered = Dropdown.prototype.render.call(this);
            // Add ability for a placeholder in the search box
            let $clear = $(
                '<span class="select2-selection__clear">' +
                'очистить' +
                '</span>'
            );

            $rendered.append($clear);
            return $rendered;
        };

        // Decorate the dropdown+search with necessary containers
        let adapter = Utils.Decorate(dropdownWithClear, AttachContainer);
        adapter = Utils.Decorate(adapter, AttachBody);

        return adapter;
    });


$.fn.select2.amd.define("Select2clearAll", ["jquery"],
    function ($) {
        function ClearAll () { }

        ClearAll.prototype.render = function (decorated) {
            let $rendered = decorated.call(this);
            let clearAllText = this.options.get('clearAllText') || 'очистить';

            let $clearAll = $(
                '<span class="select2-clearAll"'+
                clearAllText +
                '</span>'
            );

            $rendered.append($clearAll);

            return $rendered;
        };

        ClearAll.prototype.bind = function (decorated, container, $container) {
            let self = this;

            decorated.call(this, container, $container);

            this.$clearAll.on('click', function (evt) {
                self.handleClearAll(evt);
            });
        };

        ClearAll.prototype.handleClearAll = function (evt) {
            this.trigger('query', {
                term: input
            });


            this._keyUpPrevented = false;
        };


        return ClearAll;
    }
);
*/

//let ArmsSelect2MultiselectTemplate= function (data) {return data.selected.length + " шт."}