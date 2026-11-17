/**
 * @file
 * Provides access to the media edit form from CKEditor 5 media widgets.
 */

(function (Drupal, CKEditor5) {
  class MediaEdit extends CKEditor5.core.Plugin {
    static get pluginName() {
      return 'JccMediaEdit';
    }

    init() {
      const { editor } = this;
      editor.ui.componentFactory.add('jccEditMedia', (locale) => {
        const button = new CKEditor5.ui.ButtonView(locale);
        button.set({
          label: Drupal.t('Edit media (opens in new tab)'),
          withText: true,
          tooltip: true,
        });

        const refresh = () => {
          const selected = editor.model.document.selection.getSelectedElement();
          button.isEnabled =
            !editor.isReadOnly &&
            selected?.is('element', 'drupalMedia') &&
            !!selected.getAttribute('drupalMediaEntityUuid');
        };
        this.listenTo(editor.model.document.selection, 'change', refresh);
        this.listenTo(editor, 'change:isReadOnly', refresh);
        refresh();

        this.listenTo(button, 'execute', () => {
          const selected = editor.model.document.selection.getSelectedElement();
          const uuid = selected?.getAttribute('drupalMediaEntityUuid');
          if (uuid) {
            // Entity Embed redirects by UUID; the media form enforces access.
            const url = Drupal.url(
              `entity-embed/edit-embedded/media/${encodeURIComponent(uuid)}`,
            );
            window.open(url, '_blank', 'noopener,noreferrer');
          }
        });
        return button;
      });
    }
  }

  CKEditor5.jccMediaEdit = { MediaEdit };
})(Drupal, CKEditor5);
