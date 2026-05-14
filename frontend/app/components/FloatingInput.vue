<template>
  <div class="form-control">
    <input 
      :type="type" 
      :required="required" 
      :value="modelValue" 
      @input="$emit('update:modelValue', $event.target.value)" 
    />
    <label>
      <span 
        v-for="(char, index) in label.split('')" 
        :key="index"
        :style="{ transitionDelay: `${index * 50}ms` }"
      >{{ char === ' ' ? '\u00A0' : char }}</span>
    </label>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: String, default: '' },
  type: { type: String, default: 'text' },
  label: { type: String, required: true },
  required: { type: Boolean, default: true }
})
defineEmits(['update:modelValue'])
</script>

<style scoped>
.form-control {
  position: relative;
  /* Adjust margin to match the spacing in the forms */
  margin-top: 10px;
  margin-bottom: 24px;
  width: 100%;
}

.form-control input {
  background-color: transparent;
  border: 0;
  border-bottom: 2px #e5e7eb solid; /* gray-200 */
  display: block;
  width: 100%;
  padding: 12px 0 8px;
  font-size: 14px;
  color: #111827; /* gray-900 */
  transition: border-bottom-color 0.3s ease;
}

.form-control input:focus,
.form-control input:valid {
  outline: 0;
  border-bottom-color: #3b82f6; /* blue-500 */
}

/* Chrome autofill background fix */
.form-control input:-webkit-autofill,
.form-control input:-webkit-autofill:hover,
.form-control input:-webkit-autofill:focus,
.form-control input:-webkit-autofill:active {
  -webkit-background-clip: text;
  -webkit-text-fill-color: #111827;
  transition: background-color 5000s ease-in-out 0s;
}

.form-control label {
  position: absolute;
  top: 12px;
  left: 0;
  pointer-events: none;
  display: flex;
}

.form-control label span {
  display: inline-block;
  font-size: 14px;
  min-width: 2px;
  color: #6b7280; /* gray-500 */
  transition: 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.form-control input:focus+label span,
.form-control input:valid+label span,
.form-control input:-webkit-autofill+label span {
  color: #3b82f6; /* blue-500 */
  transform: translateY(-24px);
  font-size: 12px;
}
</style>
