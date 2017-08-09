var EmojiEmo = (function() {
  function EmojiEmo(el, saveUrl, id, type) {
    this.el = el;
    this.id = id;
    this.type = type;
    this.existingEl = this.el.find('.emojiemo-existing');
    this.addLink = this.el.find('.emojiemo-add');
    this.selectorEl = this.el.find('.emojiemo-selector');

    this.emotions = {};

    this.saveUrl = saveUrl;
    this.registerListeners();
    this.loadAll();
    this.refresh();
  }

  EmojiEmo.chunk = function(list, chunkSize) {
    var array=list;
    return [].concat.apply([],
       array.map(function(elem,i) {
         return i%chunkSize ? [] : [array.slice(i,i+chunkSize)];
       })
      );
  }

  EmojiEmo.prototype.registerListeners = function() {
    var self = this;

    jQuery('body').on('click', function() {
      self.selectorEl.hide();
    });

    this.addLink.on('click', function(evt) {
      evt.stopPropagation();
      self.selectorEl.show();
    });

    this.el.on('click', '.emojiemo-emotion', function(evt) {
      self.addEmotion(jQuery(this).data('emoji'));
    });

    this.el.on('click', '.emojiemo-selector-header', function(evt) {
      evt.stopPropagation();
      var header = jQuery(this).data('group');
      jQuery('.emojiemo-selector-panels .emojiemo-selector-panel').hide();
      jQuery('.emojiemo-selector-panel-' + header).show();

      jQuery('.emojiemo-selector-headers .emojiemo-selector-header').removeClass('emojiemo-selector-header-active');
      jQuery(this).addClass('emojiemo-selector-header-active');
    });
  };

  EmojiEmo.prototype.renderExisting = function() {
    var tpl = "";
    for (var i in this.existing) {
      tpl += "<div class='emojiemo-existing-emoji'>";
      tpl += this.existing[i].html;
      tpl += "<span class='emojiemo-existing-count'>";
      tpl += this.existing[i].total;
      tpl += "</span>";
      tpl += "</div>";
    }
    this.existingEl.html(tpl);
  };

  EmojiEmo.prototype.renderHeaders = function(headers) {
    var tpl = "<div class='emojiemo-selector-headers'>";
    for (var i in headers) {
      tpl += headers[i];
    }
    tpl += "</div>";
    return tpl;
  };

  EmojiEmo.prototype.renderEmojis = function(emojis) {
    var tpl = "";
    var chunks = EmojiEmo.chunk(emojis, 6);
    for (var i = 0; i < chunks.length; i++) {
      tpl += "<div>";
      for (var t = 0; t < chunks[i].length; t++) {
        tpl += chunks[i][t];
      }
      tpl += "</div>";
    }
    return tpl;
  };

  EmojiEmo.prototype.renderSelector = function(data) {
    var tpl = this.renderHeaders(data.headers);
    tpl += "<div class='emojiemo-selector-panels'>";
    for (var header in data.emojis) {
      tpl += "<div class='emojiemo-selector-panel emojiemo-selector-panel-" + header + "'>";
      tpl += this.renderEmojis(data.emojis[header]);
      tpl += "</div>";
    }
    tpl += "<div class='emojiemo-attribution'>";
    tpl += "Emoji provided free by http://emojione.com</div>";
    tpl += "</div>";
    this.selectorEl.html(tpl);
    this.selectorEl.find(".emojiemo-selector-header").first().addClass("emojiemo-selector-header-active");
  };

  EmojiEmo.prototype.loadAll = function() {
    var self = this;
    var url = this.saveUrl + '?action=emojiemo_all';
    jQuery.get(url, function(data) {
      self.renderSelector(data);
    });
  };

  EmojiEmo.prototype.refresh = function() {
    var self = this;
    var url = this.saveUrl + '?action=emojiemo_get';
    url += '&resource_id='+this.id+'&resource_type='+this.type;
    jQuery.get(url, function(data) {
      self.existing = data;
      self.renderExisting();
    });
  };

  EmojiEmo.prototype.addEmotion = function(emoji) {
    var self = this;
    var url = this.saveUrl + '?action=emojiemo_add&emoji=' + emoji;
    url += '&resource_id='+this.id+'&resource_type='+this.type;
    jQuery.get(url, function(data) {
      self.existing = data;
      self.renderExisting();
    });
  };

  return EmojiEmo;
})();

jQuery(document).ready(function() {
  twemoji = null;
  jQuery('.emojiemo-widget').each(function(el) {
    var el = jQuery(this);
    new EmojiEmo(el, el.data('url'), el.data('resourceId'), el.data('resourceType'));
  });
});
