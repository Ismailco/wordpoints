/**
 * wp.wordpoints.hooks.view.ConditionGroups
 *
 * @class
 * @augments Backbone.View
 * @augments wp.wordpoints.hooks.view.Base
 */
var Base = wp.wordpoints.hooks.view.Base,
	ConditionGroupView = wp.wordpoints.hooks.view.ConditionGroup,
	ArgHierarchySelector = wp.wordpoints.hooks.view.ArgHierarchySelector,
	ConditionSelector = wp.wordpoints.hooks.view.ConditionSelector,
	Extensions = wp.wordpoints.hooks.Extensions,
	Args = wp.wordpoints.hooks.Args,
	template = wp.wordpoints.hooks.template,
	$cache = wp.wordpoints.$cache,
	ConditionGroups;

ConditionGroups = Base.extend({

	namespace: 'condition-groups',

	className: 'conditions',

	template: template( 'hook-condition-groups' ),

	events: {
		'click > .conditions-title .add-new':           'showArgSelector',
		'click > .add-condition-form .confirm-add-new': 'maybeAddNew',
		'click > .add-condition-form .cancel-add-new':  'cancelAddNew'
	},

	initialize: function () {

		this.Conditions = Extensions.get( 'conditions' );

		this.listenTo( this.collection, 'add', this.addOne );
		this.listenTo( this.collection, 'reset', this.render );

		this.listenTo( this.reaction, 'cancel', this.cancelAddNew );

		this.collection.on( 'update', this.reaction.lockOpen, this.reaction );
		this.collection.on( 'reset', this.reaction.lockOpen, this.reaction );
	},

	render: function () {

		this.$el.html( this.template() );

		this.$c = $cache.call( this, this.$ );

		this.addAll();

		this.trigger( 'render', this );

		// See https://github.com/WordPoints/wordpoints/issues/520.
		if ( this.ArgSelector ) {

			this.$( '> .add-condition-form .arg-selectors' ).replaceWith(
				this.ArgSelector.$el
			);

			this.$( '> .add-condition-form .condition-selector' ).replaceWith(
				this.ConditionSelector.$el
			);

			this.ArgSelector.delegateEvents();
			this.ConditionSelector.delegateEvents();
			this.ConditionSelector.triggerChange();
		}

		return this;
	},

	addAll: function () {
		this.collection.each( this.addOne, this );
	},

	addOne: function ( ConditionGroup ) {

		var view = new ConditionGroupView({
			model: ConditionGroup,
			reaction: this.reaction
		});

		this.$c( '> .condition-groups' ).append( view.render().$el );
	},

	showArgSelector: function () {

		this.$c( '> .conditions-title .add-new' ).attr( 'disabled', true );

		if ( typeof this.ArgSelector === 'undefined' ) {

			var args = this.collection.getArgs();
			var Conditions = this.Conditions;
			var argFilters = Conditions.argFilters;
			var isEntityArray = ( this.collection.hierarchy.slice( -2 ).toString() === 'settings,conditions' );
			var hasConditions = function ( arg ) {

				var dataType = Conditions.getDataTypeFromArg( arg );
				var conditions = Conditions.getByDataType( dataType );

				for ( var i = 0; i < argFilters.length; i++ ) {
					if ( ! argFilters[ i ]( arg, dataType, isEntityArray, conditions ) ) {
						return false;
					}
				}

				return ! _.isEmpty( conditions );
			};

			var hierarchies = Args.getHierarchiesMatching(
				{ top: args.models, end: hasConditions }
			);

			if ( _.isEmpty( hierarchies ) ) {

				this.$c( '> .add-condition-form .no-conditions' ).show();

			} else {

				this.ArgSelector = new ArgHierarchySelector({
					hierarchies: hierarchies,
					el: this.$( '> .add-condition-form .arg-selectors' )
				});

				this.listenTo( this.ArgSelector, 'change', this.maybeShowConditionSelector );

				this.ArgSelector.render();

				this.ArgSelector.$select.change();
			}
		}

		this.$c( '> .add-condition-form' ).slideDown();
	},

	getArgType: function ( arg ) {

		var argType;

		if ( ! arg || ! arg.get ) {
			return;
		}

		argType = this.Conditions.getDataTypeFromArg( arg );

		// We compress relationships to avoid redundancy.
		if ( 'relationship' === argType ) {
			argType = this.getArgType( arg.getChild( arg.get( 'secondary' ) ) );
		}

		return argType;
	},

	maybeShowConditionSelector: function ( argSelectors, arg ) {

		var argType = this.getArgType( arg );

		if ( ! argType ) {
			if ( this.$conditionSelector ) {
				this.$conditionSelector.hide();
			}

			return;
		}

		var conditions = this.Conditions.getByDataType( argType );

		if ( ! this.ConditionSelector ) {

			this.ConditionSelector = new ConditionSelector({
				el: this.$( '> .add-condition-form .condition-selector' )
			});

			this.listenTo( this.ConditionSelector, 'change', this.conditionSelectionChange );

			this.$conditionSelector = this.ConditionSelector.$el;
		}

		this.ConditionSelector.collection.reset( _.toArray( conditions ) );

		this.$conditionSelector.show().find( 'select' ).change();
	},

	cancelAddNew: function () {

		this.$c( '> .add-condition-form' ).slideUp();
		this.$c( '> .conditions-title .add-new' ).attr( 'disabled', false );
	},

	conditionSelectionChange: function ( selector, value ) {

		this.$c( '> .add-condition-form .confirm-add-new' )
			.attr( 'disabled', ! value );
	},

	maybeAddNew: function () {

		var selected = this.ConditionSelector.getSelected();

		if ( ! selected ) {
			return;
		}

		var hierarchy = this.ArgSelector.getHierarchy(),
			id = this.collection.getIdFromHierarchy( hierarchy ),
			ConditionGroup = this.collection.get( id );

		if ( ! ConditionGroup ) {
			ConditionGroup = this.collection.add({
				id: id,
				hierarchy: hierarchy,
				groups: this.collection
			});
		}

		ConditionGroup.add( { type: selected } );

		wp.a11y.speak( this.Conditions.data.l10n.added_condition );

		this.$c( '> .add-condition-form' ).hide();
		this.$c( '> .conditions-title .add-new' ).attr( 'disabled', false );
	}
});

module.exports = ConditionGroups;
