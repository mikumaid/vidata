<?php
// includes/footer.php
?>
  </main>
  <script>
    // Initialize video player
    <?php if ($currentVideo): ?>
    var player = new Playerjs({
      id: "video-player",
      file: "<?php echo htmlspecialchars($currentVideo['file_path'], ENT_QUOTES, 'UTF-8'); ?>",
      width: 100,
      poster: "<?php echo $thumbnailUrl; ?>"
    });
    <?php endif; ?>
    
    // Initialize Tagify with existing values
    const categoryInput = document.querySelector('#category-input');
    const tagsInput = document.querySelector('#tags-input');
    
    const categoryTagify = new Tagify(categoryInput, {
      whitelist: <?php echo json_encode($videoData['categories']); ?>,
      dropdown: {
        enabled: 0,
        maxItems: 10,
        closeOnSelect: false
      }
    });
    
    const tagsTagify = new Tagify(tagsInput, {
      whitelist: <?php echo json_encode($videoData['tags']); ?>,
      dropdown: {
        enabled: 0,
        maxItems: 10,
        closeOnSelect: false
      }
    });
    
    // Set existing values if editing a processed video
    <?php if (!empty($videoData['videoCategories'])): ?>
      categoryTagify.addTags(<?php echo json_encode($videoData['videoCategories']); ?>);
    <?php endif; ?>
    
    <?php if (!empty($videoData['videoTags'])): ?>
      tagsTagify.addTags(<?php echo json_encode($videoData['videoTags']); ?>);
    <?php endif; ?>
    
    // Set existing form values
    <?php if ($currentVideo): ?>
      $('#video-orientation').val('<?php echo $currentVideo['orientation'] ?? ''; ?>');
      $('#video-duration').val('<?php echo $currentVideo['duration'] ?? ''; ?>');
      $('#video-quality').val('<?php echo $currentVideo['quality'] ?? ''; ?>');
      $('#flagged-switch').prop('checked', <?php echo ($currentVideo['flagged'] ?? 0) ? 'true' : 'false'; ?>);
    <?php endif; ?>
    
    // Save functionality
    $('#save-btn').click(function() {
      const videoId = <?php echo $currentVideo ? $currentVideo['id'] : 'null'; ?>;
      if (!videoId) return;
      
      const data = {
        video_id: videoId,
        orientation: $('#video-orientation').val(),
        duration: $('#video-duration').val(),
        quality: $('#video-quality').val(),
        categories: categoryTagify.value.map(tag => {
          return typeof tag === 'object' ? tag.value : tag;
        }),
        tags: tagsTagify.value.map(tag => {
          return typeof tag === 'object' ? tag.value : tag;
        }),
        flagged: $('#flagged-switch').is(':checked') ? 1 : 0
      };
      
      $.post('ajax/save_video.php', data, function(response) {
        if (response.success) {
          window.location.href = 'index.php';
        } else {
          alert('Error saving video: ' + response.error);
        }
      }).fail(function(xhr, status, error) {
        alert('Failed to save video: ' + error);
      });
    });
    
    // Skip functionality
    $('#skip-btn').click(function() {
      const videoId = <?php echo $currentVideo ? $currentVideo['id'] : 'null'; ?>;
      if (!videoId) return;
      
      $.post('ajax/skip_video.php', {video_id: videoId}, function(response) {
        if (response.success) {
          window.location.href = 'index.php';
        } else {
          alert('Error skipping video: ' + response.error);
        }
      }).fail(function(xhr, status, error) {
        alert('Failed to skip video: ' + error);
      });
    });
    
    // Delete functionality
    $('#delete-btn').click(function() {
      const videoId = <?php echo $currentVideo ? $currentVideo['id'] : 'null'; ?>;
      if (!videoId) return;
      
      if (!confirm('Are you sure you want to delete this video?\nThis will remove both the database entry and the actual file!')) {
        return;
      }
      
      $.post('ajax/delete_video.php', {video_id: videoId}, function(response) {
        if (response.success) {
          alert('Video deleted successfully!');
          window.location.href = 'index.php';
        } else {
          alert('Error deleting video: ' + response.error);
        }
      }).fail(function(xhr, status, error) {
        alert('Failed to delete video: ' + error);
      });
    });
    
    // Rating functionality
    $('.rating-star').click(function() {
      const videoId = <?php echo $currentVideo ? $currentVideo['id'] : 'null'; ?>;
      const rating = $(this).data('rating');
      
      if (!videoId) return;
      
      $.post('ajax/save_rating.php', {
        video_id: videoId,
        rating: rating
      }, function(response) {
        if (response.success) {
          // Update UI
          $('.rating-star').removeClass('active');
          $('.rating-star').each(function() {
            if ($(this).data('rating') <= response.rating) {
              $(this).addClass('active');
            }
          });
          
          // Update rating text
          const ratingText = ['Unrated', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
          $('.rating-text').text(ratingText[response.rating]);
          
          // Show clear button
          if (response.rating > 0 && $('#clear-rating').length === 0) {
            $('.rating-text').after('<button class="btn btn-sm btn-outline-danger ms-2" id="clear-rating"><i class="fa-solid fa-xmark"></i> Clear</button>');
          }
        } else {
          alert('Error saving rating: ' + response.error);
        }
      }).fail(function() {
        alert('Failed to save rating');
      });
    });
    
    // Clear rating
    $(document).on('click', '#clear-rating', function() {
      const videoId = <?php echo $currentVideo ? $currentVideo['id'] : 'null'; ?>;
      if (!videoId) return;
      
      $.post('ajax/save_rating.php', {
        video_id: videoId,
        rating: 0
      }, function(response) {
        if (response.success) {
          // Update UI
          $('.rating-star').removeClass('active');
          $('.rating-text').text('Not rated');
          $('#clear-rating').remove();
        } else {
          alert('Error clearing rating: ' + response.error);
        }
      }).fail(function() {
        alert('Failed to clear rating');
      });
    });
  </script>
</body>
</html>