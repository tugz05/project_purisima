<script setup lang="ts">
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import { FileText, Bot } from 'lucide-vue-next';

interface FormField {
    name: string;
    label: string;
    type: string;
    required?: boolean;
    placeholder?: string;
    options?: string[];
    default?: any;
}

interface Props {
    documentType: {
        id: number;
        name: string;
        description: string;
        base_fee: number;
        use_ai_generation: boolean;
        required_fields: FormField[];
    };
    formData: Record<string, any>;
    errors: Record<string, string>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:formData': [value: Record<string, any>];
}>();

const localFormData = ref({ ...props.formData });

watch(localFormData, (newData) => {
    emit('update:formData', newData);
}, { deep: true });

const getFieldValue = (fieldName: string) => {
    return localFormData.value[fieldName] || '';
};

const setFieldValue = (fieldName: string, value: any) => {
    localFormData.value[fieldName] = value;
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <component :is="documentType.use_ai_generation ? Bot : FileText" class="h-5 w-5" />
                {{ documentType.name }}
                <span v-if="documentType.use_ai_generation" class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                    AI-Powered
                </span>
            </CardTitle>
            <CardDescription>
                {{ documentType.description }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div class="space-y-4">
                <!-- Dynamic Fields -->
                <div v-for="field in documentType.required_fields" :key="field.name" class="space-y-2">
                    <Label :for="field.name">
                        {{ field.label }}
                        <span v-if="field.required" class="text-red-500">*</span>
                    </Label>

                    <!-- Text Input -->
                    <Input
                        v-if="field.type === 'text'"
                        :id="field.name"
                        :value="getFieldValue(field.name)"
                        @input="setFieldValue(field.name, $event.target.value)"
                        :placeholder="field.placeholder"
                        :required="field.required"
                    />

                    <!-- Textarea -->
                    <Textarea
                        v-else-if="field.type === 'textarea'"
                        :id="field.name"
                        :value="getFieldValue(field.name)"
                        @input="setFieldValue(field.name, $event.target.value)"
                        :placeholder="field.placeholder"
                        :required="field.required"
                        rows="3"
                    />

                    <!-- Number Input -->
                    <Input
                        v-else-if="field.type === 'number'"
                        :id="field.name"
                        :value="getFieldValue(field.name)"
                        @input="setFieldValue(field.name, parseFloat($event.target.value) || 0)"
                        type="number"
                        :placeholder="field.placeholder"
                        :required="field.required"
                    />

                    <!-- Date Input -->
                    <Input
                        v-else-if="field.type === 'date'"
                        :id="field.name"
                        :value="getFieldValue(field.name)"
                        @input="setFieldValue(field.name, $event.target.value)"
                        type="date"
                        :required="field.required"
                    />

                    <!-- Select Dropdown -->
                    <Select
                        v-else-if="field.type === 'select'"
                        :value="getFieldValue(field.name)"
                        @update:value="setFieldValue(field.name, $event)"
                    >
                        <SelectTrigger :id="field.name">
                            <SelectValue :placeholder="field.placeholder || `Select ${field.label}`" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in field.options"
                                :key="option"
                                :value="option"
                            >
                                {{ option }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Error Message -->
                    <InputError :message="errors[field.name]" />
                </div>

                <!-- Fee Display -->
                <div v-if="documentType.base_fee > 0" class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="font-medium">Document Fee:</span>
                        <span class="text-lg font-bold text-blue-600">₱{{ documentType.base_fee.toFixed(2) }}</span>
                    </div>
                </div>

                <!-- AI Generation Notice -->
                <div v-if="documentType.use_ai_generation" class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                    <div class="flex items-start gap-2">
                        <Bot class="h-5 w-5 text-blue-600 mt-0.5" />
                        <div>
                            <h4 class="font-medium text-blue-900">AI-Powered Generation</h4>
                            <p class="text-sm text-blue-700 mt-1">
                                This document will be automatically generated using AI based on your provided information.
                                The AI will create professional, properly formatted content that matches official barangay document standards.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>

