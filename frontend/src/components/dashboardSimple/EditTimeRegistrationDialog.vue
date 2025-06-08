<template>
  <v-dialog
    :model-value="dialogVisible"
    @update:model-value="$emit('update:dialogVisible', $event)"
    max-width="500px"
  >
    <v-card>
      <v-card-title>Edit Time Registration</v-card-title>
      <v-card-text>
        <v-container>
          <v-row>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="editableItem.clock_in"
                label="Clock In Time"
                type="time"
                hint="Format: HH:MM"
              ></v-text-field>
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="editableItem.clock_out"
                label="Clock Out Time"
                type="time"
                hint="Format: HH:MM"
                clearable
              ></v-text-field>
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="error" text @click="onCancel">Cancel</v-btn>
        <v-btn color="primary" text @click="onSave">Save</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watchEffect, defineProps, defineEmits } from 'vue';

const props = defineProps({
  dialogVisible: {
    type: Boolean,
    required: true,
  },
  editedItemData: {
    type: Object,
    default: () => ({ clock_in: '', clock_out: null }) // Default to prevent issues with stringify
  }
});

const emit = defineEmits(['update:dialogVisible', 'save-registration', 'close-dialog']);

const editableItem = ref({});

watchEffect(() => {
  // Create a deep copy to avoid mutating the prop directly
  // Ensure there's always an object for clock_in and clock_out, even if null from prop
  editableItem.value = JSON.parse(JSON.stringify(props.editedItemData || { clock_in: '', clock_out: null }));
  if (editableItem.value.clock_in === null || editableItem.value.clock_in === undefined) {
    editableItem.value.clock_in = '';
  }
  // clock_out can be null, so empty string for the input if it is.
  if (editableItem.value.clock_out === null || editableItem.value.clock_out === undefined) {
    editableItem.value.clock_out = '';
  }
});

const onSave = () => {
  emit('save-registration', { ...editableItem.value });
  emit('update:dialogVisible', false); // Close dialog on save
};

const onCancel = () => {
  emit('update:dialogVisible', false);
  emit('close-dialog'); // Emit close-dialog for any additional cleanup parent might need
};

</script>
