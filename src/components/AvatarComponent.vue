<template>
    
      <div v-if="profileImg === 'avatar'" :style="{'background-color': avatarColor}" class="avatar">
          {{initials}}
      </div>
      <img v-else :src="profileImg" />
 
  </template>
  
  <script>


export default {
  props: {
    profileImg: String,
    firstName: {
      type: String,
      default: ""
    },
    lastName: {
      type: String,
      default: ""
    },
    avatarColor: {
      type: String,
      default: "#ccc"
    }
  },
  computed: {
    initials() {
      return this.firstName.charAt(0) + this.lastName.charAt(0)
    }
  }
}
</script>


  <style scoped>
  .avatar {
      width: 36px;
      height: 36px;
      font-size: 1.125rem;
      font-weight: bold;
      text-align: center;
      line-height: 36px;
      border-radius: 50%;
      color: #fff;
  }

  img {
    width: 36px;
      height: 36px;
    border-radius: 50%;
    object-fit: cover;
  }
  </style>