<style>
form {
  display: block;
  height: 400px;
  width: 400px;
  background: #ccc;
  margin: auto;
  margin-top: 40px;
  text-align: center;
  line-height: 400px;
  border-radius: 4px;
}

div.file-listing {
  width: 400px;
  margin: auto;
  padding: 10px;
  border-bottom: 1px solid #ddd;
}

div.file-listing img {
  height: 100px;
}

div.remove-container {
  text-align: center;
}

div.remove-container a {
  color: red;
  cursor: pointer;
}

a.submit-button {
  display: block;
  margin: auto;
  text-align: center;
  width: 200px;
  padding: 10px;
  text-transform: uppercase;
  background-color: #ccc;
  color: white;
  font-weight: bold;
  margin-top: 20px;
}

progress {
  width: 400px;
  margin: auto;
  display: block;
  margin-top: 20px;
  margin-bottom: 20px;
}
</style>

<template>
  <ion-page></ion-page>
  <!--<div id="file-drag-drop">
    <form ref="fileform">
      <span class="drop-files">Drop the files here!</span>
    </form>

    <progress max="100" :value.prop="uploadPercentage"></progress>

    <div v-for="(file, key) in files" class="file-listing">
      <img class="preview" v-bind:ref="'preview'+parseInt( key )"/>
      {{ file.name }}
      <div class="remove-container">
        <a class="remove" v-on:click="removeFile( key )">Remove</a>
      </div>
    </div>

    <a class="submit-button" v-on:click="submitFiles()" v-show="files.length > 0">Submit</a>
  </div>-->
</template>

<script>
/*
import axios from 'axios';

  export default {
    
      Variables used by the drag and drop component
    
    data(){
      return {
        dragAndDropCapable: false,
        files: [],
        uploadPercentage: 0
      }
    },

    mounted(){
      
        Determine if drag and drop functionality is capable in the browser
      
      this.dragAndDropCapable = this.determineDragAndDropCapable();

      
        If drag and drop capable, then we continue to bind events to our elements.
      
      if( this.dragAndDropCapable ){
        
          Listen to all of the drag events and bind an event listener to each
          for the fileform.
        
        ['drag', 'dragstart', 'dragend', 'dragover', 'dragenter', 'dragleave', 'drop'].forEach( function( evt ) {
          
            For each event add an event listener that prevents the default action
            (opening the file in the browser) and stop the propagation of the event (so
            no other elements open the file in the browser)
          
          this.$refs.fileform.addEventListener(evt, function(e){
            e.preventDefault();
            e.stopPropagation();
          }.bind(this), false);
        }.bind(this));

        
          Add an event listener for drop to the form
        
        this.$refs.fileform.addEventListener('drop', function(e){
          
            Capture the files from the drop event and add them to our local files
            array.
          
          for( let i = 0; i < e.dataTransfer.files.length; i++ ){
            this.files.push( e.dataTransfer.files[i] );
            this.getImagePreviews();
          }
        }.bind(this));
      }
    },

    methods: {
      
        Determines if the drag and drop functionality is in the
        window
      
      determineDragAndDropCapable(){
 
        var div = document.createElement('div');

        return ( ( 'draggable' in div )
                || ( 'ondragstart' in div && 'ondrop' in div ) )
                && 'FormData' in window
                && 'FileReader' in window;
      },

      getImagePreviews(){
    
        for( let i = 0; i < this.files.length; i++ ){
       
          if ( /\.(jpe?g|png|gif)$/i.test( this.files[i].name ) ) {
        
            let reader = new FileReader();

            reader.addEventListener("load", function(){
              this.$refs['preview'+parseInt( i )][0].src = reader.result;
            }.bind(this), false);

            
            reader.readAsDataURL( this.files[i] );
          }else{
    
            this.$nextTick(function(){
              this.$refs['preview'+parseInt( i )][0].src = '/images/file.png';
            });
          }
        }
      },

      submitFiles(){
    
        let formData = new FormData();

  
        for( var i = 0; i < this.files.length; i++ ){
          let file = this.files[i];

          formData.append('files[' + i + ']', file);
          formData.append('name', 'Image');
          formData.append('code', 'img');
          formData.append('project', 'alexs-blog');
          formData.append('newComponent', 'newComponent');
        }

        axios.post( '/control-center/components.php',
          formData,
          {
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: function( progressEvent ) {
              this.uploadPercentage = parseInt( Math.round( ( progressEvent.loaded * 100 ) / progressEvent.total ) );
            }.bind(this)
          }
        ).then(function(){
          console.log('SUCCESS!!');
        })
        .catch(function(){
          console.log('FAILURE!!');
        });
      },


      removeFile( key ){
        this.files.splice( key, 1 );
      }
    }
  }
  */
</script>
