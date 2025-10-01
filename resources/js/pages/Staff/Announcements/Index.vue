
<template>
  <StaffLayout :breadcrumbs="staffAnnouncementsBreadcrumbs">
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
      <!-- Enhanced Header with Gradient -->
      <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 shadow-xl">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute inset-0">
          <div class="absolute top-0 left-0 w-72 h-72 bg-white/10 rounded-full -translate-x-36 -translate-y-36"></div>
          <div class="absolute bottom-0 right-0 w-96 h-96 bg-white/5 rounded-full translate-x-48 translate-y-48"></div>
        </div>
        <div class="relative px-4 sm:px-6 lg:px-8 py-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <div class="text-white">
              <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-white/20 rounded-2xl backdrop-blur-sm">
                  <Bell class="h-8 w-8" />
                </div>
                <div>
                  <h1 class="text-4xl font-bold">Announcements</h1>
                  <p class="text-blue-100 text-lg mt-1">Manage community announcements and notices</p>
                </div>
              </div>
              <div class="flex items-center gap-6 text-sm text-blue-100">
                <div class="flex items-center gap-2">
                  <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                  <span>{{ statistics.published || 0 }} Published</span>
                </div>
                <div class="flex items-center gap-2">
                  <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                  <span>{{ statistics.draft || 0 }} Draft</span>
                </div>
                <div class="flex items-center gap-2">
                  <div class="w-2 h-2 bg-purple-400 rounded-full"></div>
                  <span>{{ statistics.featured || 0 }} Featured</span>
                </div>
              </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
              <Button @click="goToCreate" class="bg-white text-blue-600 hover:bg-blue-50 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                <Plus class="w-5 h-5 mr-2" />
                New Announcement
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Maximized Content Area -->
      <div class="px-4 sm:px-6 lg:px-8 py-6">
        <!-- Enhanced Filters Bar -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-white/20 p-6 mb-8">
          <div class="flex flex-col lg:flex-row lg:items-center gap-6">
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <div>
                <Label for="type-filter" class="text-sm font-semibold text-gray-700 mb-2 block">Type</Label>
                <Select v-model="filters.type" @update:model-value="applyFilters">
                  <SelectTrigger class="bg-white border-gray-200 hover:border-blue-300 focus:border-blue-500 transition-colors">
                    <SelectValue placeholder="All Types" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Types</SelectItem>
                    <SelectItem value="general">General</SelectItem>
                    <SelectItem value="urgent">Urgent</SelectItem>
                    <SelectItem value="event">Event</SelectItem>
                    <SelectItem value="notice">Notice</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label for="priority-filter" class="text-sm font-semibold text-gray-700 mb-2 block">Priority</Label>
                <Select v-model="filters.priority" @update:model-value="applyFilters">
                  <SelectTrigger class="bg-white border-gray-200 hover:border-blue-300 focus:border-blue-500 transition-colors">
                    <SelectValue placeholder="All Priorities" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Priorities</SelectItem>
                    <SelectItem value="urgent">Urgent</SelectItem>
                    <SelectItem value="high">High</SelectItem>
                    <SelectItem value="normal">Normal</SelectItem>
                    <SelectItem value="low">Low</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label for="status-filter" class="text-sm font-semibold text-gray-700 mb-2 block">Status</Label>
                <Select v-model="filters.published" @update:model-value="applyFilters">
                  <SelectTrigger class="bg-white border-gray-200 hover:border-blue-300 focus:border-blue-500 transition-colors">
                    <SelectValue placeholder="All Status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Status</SelectItem>
                    <SelectItem value="true">Published</SelectItem>
                    <SelectItem value="false">Draft</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="flex items-end">
                <Button variant="outline" @click="clearFilters" class="w-full bg-white border-gray-200 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200">
                  <X class="w-4 h-4 mr-2" />
                  Clear Filters
                </Button>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="text-sm text-gray-600">
                Showing {{ announcements.length }} announcement{{ announcements.length !== 1 ? 's' : '' }}
              </div>
              <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" class="bg-white border-gray-200 hover:bg-gray-50">
                  <Edit class="w-4 h-4 mr-1" />
                  Bulk Actions
                </Button>
                <Button variant="outline" size="sm" class="bg-white border-gray-200 hover:bg-gray-50">
                  <Download class="w-4 h-4 mr-1" />
                  Export
                </Button>
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Announcements Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
          <div
            v-for="announcement in announcements"
            :key="announcement.id"
            class="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden border border-gray-100 transform hover:-translate-y-2"
          >
            <!-- Priority Indicator -->
            <div
              class="absolute top-4 right-4 z-10"
              :class="{
                'bg-red-500': announcement.priority === 'urgent',
                'bg-orange-500': announcement.priority === 'high',
                'bg-blue-500': announcement.priority === 'normal',
                'bg-gray-500': announcement.priority === 'low'
              }"
            >
              <div class="px-3 py-1 text-white text-xs font-semibold rounded-full">
                {{ announcement.priority.toUpperCase() }}
              </div>
            </div>

            <!-- Featured Badge -->
            <div v-if="announcement.is_featured" class="absolute top-4 left-4 z-10">
              <div class="px-3 py-1 bg-purple-500 text-white text-xs font-semibold rounded-full flex items-center gap-1">
                <Star class="w-3 h-3" />
                FEATURED
              </div>
            </div>

            <!-- Image -->
            <div v-if="announcement.image_path" class="relative h-48 overflow-hidden">
              <img
                :src="`/storage/${announcement.image_path}`"
                :alt="announcement.title"
                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
            </div>

            <!-- Content -->
            <div class="p-6">
              <!-- Type Badge -->
              <div class="mb-4">
                <span
                  class="px-3 py-1 text-xs font-semibold rounded-full"
                  :class="{
                    'bg-red-100 text-red-800': announcement.type === 'urgent',
                    'bg-green-100 text-green-800': announcement.type === 'event',
                    'bg-blue-100 text-blue-800': announcement.type === 'notice',
                    'bg-gray-100 text-gray-800': announcement.type === 'general'
                  }"
                >
                  {{ announcement.type.toUpperCase() }}
                </span>
              </div>

              <!-- Title -->
              <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors line-clamp-2">
                {{ announcement.title }}
              </h3>

              <!-- Content Preview -->
              <p class="text-gray-600 mb-4 line-clamp-3 text-sm leading-relaxed">
                {{ announcement.excerpt }}
              </p>

              <!-- Author & Date -->
              <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                <div v-if="announcement.author_name" class="flex items-center gap-2">
                  <User class="w-4 h-4" />
                  <span class="truncate">{{ announcement.author_name }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <Calendar class="w-4 h-4" />
                  <span>{{ formatDate(announcement.published_at) }}</span>
                </div>
              </div>

              <!-- Status Indicators -->
              <div class="flex items-center gap-2 mb-4">
                <div
                  class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium"
                  :class="announcement.is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                >
                  <div
                    class="w-2 h-2 rounded-full"
                    :class="announcement.is_published ? 'bg-green-500' : 'bg-yellow-500'"
                  ></div>
                  {{ announcement.is_published ? 'Published' : 'Draft' }}
                </div>
                <div v-if="announcement.expires_at" class="flex items-center gap-1 px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs font-medium">
                  <Clock class="w-3 h-3" />
                  Expires {{ formatDate(announcement.expires_at) }}
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <div class="flex items-center gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    @click="togglePublication(announcement)"
                    :class="announcement.is_published ? 'text-green-600 hover:text-green-700 border-green-200' : 'text-gray-600 hover:text-gray-700'"
                  >
                    <Eye v-if="announcement.is_published" class="w-4 h-4" />
                    <EyeOff v-else class="w-4 h-4" />
                  </Button>

                  <Button
                    variant="outline"
                    size="sm"
                    @click="toggleFeatured(announcement)"
                    :class="announcement.is_featured ? 'text-purple-600 hover:text-purple-700 border-purple-200' : 'text-gray-600 hover:text-gray-700'"
                  >
                    <Star class="w-4 h-4" />
                  </Button>
                </div>

                <div class="flex items-center gap-1">
                  <Button variant="outline" size="sm" @click="openViewSheet(announcement)" class="text-blue-600 hover:text-blue-700 border-blue-200">
                    <Eye class="w-4 h-4" />
                  </Button>

                  <Button variant="outline" size="sm" @click="goToEdit(announcement)" class="text-gray-600 hover:text-gray-700">
                    <Edit class="w-4 h-4" />
                  </Button>

                  <Button
                    variant="outline"
                    size="sm"
                    @click="openDeleteDialog(announcement)"
                    class="text-red-600 hover:text-red-700 border-red-200"
                  >
                    <Trash2 class="w-4 h-4" />
                  </Button>
                </div>
              </div>
            </div>

            <!-- Hover Effect Overlay -->
            <div class="absolute inset-0 bg-gradient-to-r from-blue-500/5 to-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
          </div>

        </div>

        <!-- Enhanced Empty State -->
        <div v-if="announcements.length === 0" class="col-span-full">
          <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-white/20 p-12 text-center">
            <div class="w-24 h-24 bg-gradient-to-r from-blue-100 to-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <Bell class="w-12 h-12 text-blue-600" />
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-3">No announcements yet</h3>
            <p class="text-gray-600 mb-8 max-w-md mx-auto">Start engaging with your community by creating your first announcement. Share important news, events, and updates.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
              <Button @click="goToCreate" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                <Plus class="w-5 h-5 mr-2" />
                Create Your First Announcement
              </Button>
              <Button variant="outline" class="border-gray-200 hover:bg-gray-50">
                <FileText class="w-5 h-5 mr-2" />
                View Templates
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Sheet -->
    <Sheet :open="createSheetOpen" @update:open="createSheetOpen = $event">
      <SheetContent class="w-full sm:w-[800px] lg:w-[1000px] xl:w-[1200px] overflow-y-auto">
        <!-- Enhanced Header with Gradient -->
        <SheetHeader>
          <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 text-white p-6 rounded-lg -m-6 mb-6">
            <div class="flex items-center gap-3 mb-2">
              <div class="p-2 bg-white/20 rounded-lg">
                <Bell class="w-6 h-6" />
              </div>
              <SheetTitle class="text-2xl">Create New Announcement</SheetTitle>
            </div>
            <SheetDescription class="text-blue-100 text-base">
              Share important information with the community. Fill out the form below to create your announcement.
            </SheetDescription>
          </div>
        </SheetHeader>

        <form @submit.prevent="submitCreate" class="space-y-8">
          <!-- Basic Information Section -->
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl border border-blue-200">
            <div class="flex items-center gap-2 mb-4">
              <div class="p-2 bg-blue-100 rounded-lg">
                <FileText class="w-5 h-5 text-blue-600" />
              </div>
              <h3 class="text-lg font-semibold text-blue-900">Basic Information</h3>
            </div>

            <div class="space-y-6">
              <div>
                <Label for="title" class="text-sm font-semibold text-gray-700 mb-2 block">
                  Announcement Title *
                </Label>
                <Input
                  id="title"
                  v-model="createForm.title"
                  placeholder="Enter a clear and descriptive title"
                  class="bg-white border-gray-200 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200"
                  :class="{ 'border-red-500 ring-red-500': createForm.errors.title }"
                />
                <p v-if="createForm.errors.title" class="text-red-500 text-sm mt-2 flex items-center gap-1">
                  <X class="w-4 h-4" />
                  {{ createForm.errors.title }}
                </p>
                <p v-else class="text-gray-500 text-sm mt-1">Make it clear and attention-grabbing</p>
              </div>

              <div>
                <Label for="content" class="text-sm font-semibold text-gray-700 mb-2 block">
                  Content *
                </Label>
                <Textarea
                  id="content"
                  v-model="createForm.content"
                  placeholder="Write your announcement content here. Be clear, concise, and informative."
                  rows="6"
                  class="bg-white border-gray-200 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 resize-none"
                  :class="{ 'border-red-500 ring-red-500': createForm.errors.content }"
                />
                <p v-if="createForm.errors.content" class="text-red-500 text-sm mt-2 flex items-center gap-1">
                  <X class="w-4 h-4" />
                  {{ createForm.errors.content }}
                </p>
                <p v-else class="text-gray-500 text-sm mt-1">Provide detailed information for your audience</p>
              </div>
            </div>
          </div>

          <!-- Classification Section -->
          <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-6 rounded-xl border border-purple-200">
            <div class="flex items-center gap-2 mb-4">
              <div class="p-2 bg-purple-100 rounded-lg">
                <Star class="w-5 h-5 text-purple-600" />
              </div>
              <h3 class="text-lg font-semibold text-purple-900">Classification & Priority</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div>
                <Label for="type" class="text-sm font-semibold text-gray-700 mb-2 block">
                  Type *
                </Label>
                <Select v-model="createForm.type">
                  <SelectTrigger class="bg-white border-gray-200 hover:border-purple-300 focus:border-purple-500 transition-colors">
                    <SelectValue placeholder="Select announcement type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="general">📢 General</SelectItem>
                    <SelectItem value="urgent">🚨 Urgent</SelectItem>
                    <SelectItem value="event">📅 Event</SelectItem>
                    <SelectItem value="notice">📋 Notice</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="createForm.errors.type" class="text-red-500 text-sm mt-2 flex items-center gap-1">
                  <X class="w-4 h-4" />
                  {{ createForm.errors.type }}
                </p>
                <p v-else class="text-gray-500 text-sm mt-1">Choose the appropriate category</p>
              </div>

              <div>
                <Label for="priority" class="text-sm font-semibold text-gray-700 mb-2 block">
                  Priority Level *
                </Label>
                <Select v-model="createForm.priority">
                  <SelectTrigger class="bg-white border-gray-200 hover:border-purple-300 focus:border-purple-500 transition-colors">
                    <SelectValue placeholder="Select priority level" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="low">🟢 Low Priority</SelectItem>
                    <SelectItem value="normal">🟡 Normal Priority</SelectItem>
                    <SelectItem value="high">🟠 High Priority</SelectItem>
                    <SelectItem value="urgent">🔴 Urgent Priority</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="createForm.errors.priority" class="text-red-500 text-sm mt-2 flex items-center gap-1">
                  <X class="w-4 h-4" />
                  {{ createForm.errors.priority }}
                </p>
                <p v-else class="text-gray-500 text-sm mt-1">Set the importance level</p>
              </div>
            </div>
          </div>

          <!-- Scheduling Section -->
          <div class="bg-gradient-to-r from-green-50 to-teal-50 p-6 rounded-xl border border-green-200">
            <div class="flex items-center gap-2 mb-4">
              <div class="p-2 bg-green-100 rounded-lg">
                <Calendar class="w-5 h-5 text-green-600" />
              </div>
              <h3 class="text-lg font-semibold text-green-900">Scheduling & Timing</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div>
                <Label for="published_at" class="text-sm font-semibold text-gray-700 mb-2 block">
                  Publish Date & Time
                </Label>
                <Input
                  id="published_at"
                  v-model="createForm.published_at"
                  type="datetime-local"
                  class="bg-white border-gray-200 focus:border-green-500 focus:ring-green-500 transition-all duration-200"
                />
                <p v-if="createForm.errors.published_at" class="text-red-500 text-sm mt-2 flex items-center gap-1">
                  <X class="w-4 h-4" />
                  {{ createForm.errors.published_at }}
                </p>
                <p v-else class="text-gray-500 text-sm mt-1">Leave empty to publish immediately</p>
              </div>

              <div>
                <Label for="expires_at" class="text-sm font-semibold text-gray-700 mb-2 block">
                  Expiration Date & Time
                </Label>
                <Input
                  id="expires_at"
                  v-model="createForm.expires_at"
                  type="datetime-local"
                  class="bg-white border-gray-200 focus:border-green-500 focus:ring-green-500 transition-all duration-200"
                />
                <p v-if="createForm.errors.expires_at" class="text-red-500 text-sm mt-2 flex items-center gap-1">
                  <X class="w-4 h-4" />
                  {{ createForm.errors.expires_at }}
                </p>
                <p v-else class="text-gray-500 text-sm mt-1">Optional - when to stop showing this announcement</p>
              </div>
            </div>
          </div>

          <!-- Author Information Section -->
          <div class="bg-gradient-to-r from-orange-50 to-yellow-50 p-6 rounded-xl border border-orange-200">
            <div class="flex items-center gap-2 mb-4">
              <div class="p-2 bg-orange-100 rounded-lg">
                <User class="w-5 h-5 text-orange-600" />
              </div>
              <h3 class="text-lg font-semibold text-orange-900">Author Information</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div>
                <Label for="author_name" class="text-sm font-semibold text-gray-700 mb-2 block">
                  Author Name
                </Label>
                <Input
                  id="author_name"
                  v-model="createForm.author_name"
                  placeholder="Enter author's full name"
                  class="bg-white border-gray-200 focus:border-orange-500 focus:ring-orange-500 transition-all duration-200"
                />
                <p class="text-gray-500 text-sm mt-1">Who is publishing this announcement?</p>
              </div>

              <div>
                <Label for="author_position" class="text-sm font-semibold text-gray-700 mb-2 block">
                  Author Position
                </Label>
                <Input
                  id="author_position"
                  v-model="createForm.author_position"
                  placeholder="Enter author's position or title"
                  class="bg-white border-gray-200 focus:border-orange-500 focus:ring-orange-500 transition-all duration-200"
                />
                <p class="text-gray-500 text-sm mt-1">Their role or department</p>
              </div>
            </div>
          </div>

          <!-- Media & Attachments Section -->
          <div class="bg-gradient-to-r from-indigo-50 to-blue-50 p-6 rounded-xl border border-indigo-200">
            <div class="flex items-center gap-2 mb-4">
              <div class="p-2 bg-indigo-100 rounded-lg">
                <Download class="w-5 h-5 text-indigo-600" />
              </div>
              <h3 class="text-lg font-semibold text-indigo-900">Media & Attachments</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div>
                <Label for="image" class="text-sm font-semibold text-gray-700 mb-2 block">
                  Featured Image
                </Label>
                <Input
                  id="image"
                  type="file"
                  accept="image/*"
                  @change="handleImageUpload"
                  class="bg-white border-gray-200 hover:border-indigo-300 focus:border-indigo-500 transition-all duration-200"
                />
                <p class="text-gray-500 text-sm mt-1">📷 Max 2MB. Supported: JPEG, PNG, JPG, GIF, WebP</p>
              </div>

              <div>
                <Label for="attachments" class="text-sm font-semibold text-gray-700 mb-2 block">
                  Additional Files
                </Label>
                <Input
                  id="attachments"
                  type="file"
                  multiple
                  @change="handleAttachmentsUpload"
                  class="bg-white border-gray-200 hover:border-indigo-300 focus:border-indigo-500 transition-all duration-200"
                />
                <p class="text-gray-500 text-sm mt-1">📎 Max 10MB each. PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT</p>
              </div>
            </div>
          </div>

          <!-- Publication Settings Section -->
          <div class="bg-gradient-to-r from-gray-50 to-slate-50 p-6 rounded-xl border border-gray-200">
            <div class="flex items-center gap-2 mb-4">
              <div class="p-2 bg-gray-100 rounded-lg">
                <CheckCircle class="w-5 h-5 text-gray-600" />
              </div>
              <h3 class="text-lg font-semibold text-gray-900">Publication Settings</h3>
            </div>

            <div class="space-y-4">
              <div class="flex items-center justify-between p-4 bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
                <div class="flex items-center gap-3">
                  <div class="p-2 bg-green-100 rounded-lg">
                    <CheckCircle class="w-5 h-5 text-green-600" />
                  </div>
                  <div>
                    <Label for="is_published" class="text-sm font-semibold text-gray-700 cursor-pointer">
                      Publish Immediately
                    </Label>
                    <p class="text-gray-500 text-sm">Make this announcement visible to the public right away</p>
                  </div>
                </div>
                <Switch
                  id="is_published"
                  v-model:checked="createForm.is_published"
                  class="data-[state=checked]:bg-green-600"
                />
              </div>

              <div class="flex items-center justify-between p-4 bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
                <div class="flex items-center gap-3">
                  <div class="p-2 bg-yellow-100 rounded-lg">
                    <Star class="w-5 h-5 text-yellow-600" />
                  </div>
                  <div>
                    <Label for="is_featured" class="text-sm font-semibold text-gray-700 cursor-pointer">
                      Feature This Announcement
                    </Label>
                    <p class="text-gray-500 text-sm">Highlight this announcement as important content</p>
                  </div>
                </div>
                <Switch
                  id="is_featured"
                  v-model:checked="createForm.is_featured"
                  class="data-[state=checked]:bg-yellow-600"
                />
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200">
            <Button
              type="button"
              variant="outline"
              @click="createSheetOpen = false"
              class="w-full sm:w-auto bg-white border-gray-200 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200"
            >
              <X class="w-4 h-4 mr-2" />
              Cancel
            </Button>
            <Button
              type="submit"
              :disabled="createForm.processing"
              class="w-full sm:w-auto bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white shadow-lg hover:shadow-xl transition-all duration-200"
            >
              <Loader2 v-if="createForm.processing" class="w-4 h-4 mr-2 animate-spin" />
              <Bell v-else class="w-4 h-4 mr-2" />
              {{ createForm.processing ? 'Creating...' : 'Create Announcement' }}
            </Button>
          </div>
        </form>
      </SheetContent>
    </Sheet>

    <!-- Enhanced View Sheet -->
    <Sheet :open="viewSheetOpen" @update:open="viewSheetOpen = $event">
      <SheetContent class="w-full sm:w-[900px] lg:w-[1100px] xl:w-[1300px] p-0 overflow-hidden">
        <div v-if="selectedAnnouncement" class="h-full flex flex-col">
          <!-- Enhanced Header -->
          <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-600 text-white">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="absolute inset-0">
              <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-r from-blue-600/20 to-purple-600/20"></div>
            </div>

            <div class="relative p-8">
              <!-- Close Button -->
              <div class="flex justify-end mb-4">
                <Button
                  variant="ghost"
                  size="sm"
                  @click="viewSheetOpen = false"
                  class="text-white hover:bg-white/20 p-2 rounded-full"
                >
                  <X class="w-5 h-5" />
                </Button>
              </div>

              <!-- Title and Badges -->
              <div class="space-y-4">
                <div class="flex items-start justify-between gap-4">
                  <div class="flex-1">
                    <SheetTitle class="text-3xl font-bold mb-3 leading-tight">
                      {{ selectedAnnouncement.title }}
                    </SheetTitle>
                    <SheetDescription class="text-blue-100 text-lg leading-relaxed">
                      {{ selectedAnnouncement.excerpt }}
                    </SheetDescription>
                  </div>
                </div>

                <!-- Priority and Type Badges -->
                <div class="flex flex-wrap gap-3">
                  <div
                    class="px-4 py-2 text-sm font-semibold rounded-full text-white backdrop-blur-sm"
                    :class="{
                      'bg-red-500/80': selectedAnnouncement.priority === 'urgent',
                      'bg-orange-500/80': selectedAnnouncement.priority === 'high',
                      'bg-blue-500/80': selectedAnnouncement.priority === 'normal',
                      'bg-gray-500/80': selectedAnnouncement.priority === 'low'
                    }"
                  >
                    {{ selectedAnnouncement.priority.toUpperCase() }} PRIORITY
                  </div>
                  <div
                    class="px-4 py-2 text-sm font-semibold rounded-full backdrop-blur-sm"
                    :class="{
                      'bg-red-100/90 text-red-800': selectedAnnouncement.type === 'urgent',
                      'bg-green-100/90 text-green-800': selectedAnnouncement.type === 'event',
                      'bg-blue-100/90 text-blue-800': selectedAnnouncement.type === 'notice',
                      'bg-gray-100/90 text-gray-800': selectedAnnouncement.type === 'general'
                    }"
                  >
                    {{ selectedAnnouncement.type.toUpperCase() }}
                  </div>
                  <div v-if="selectedAnnouncement.is_featured" class="px-4 py-2 bg-purple-100/90 text-purple-800 text-sm font-semibold rounded-full flex items-center gap-1 backdrop-blur-sm">
                    <Star class="w-3 h-3" />
                    FEATURED
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Scrollable Content -->
          <div class="flex-1 overflow-y-auto">
            <div class="p-8 space-y-8">
              <!-- Hero Image -->
              <div v-if="selectedAnnouncement.image_path" class="space-y-4">
                <div class="relative group">
                  <img
                    :src="`/storage/${selectedAnnouncement.image_path}`"
                    :alt="selectedAnnouncement.title"
                    class="w-full h-80 object-cover rounded-2xl shadow-2xl transition-transform duration-300 group-hover:scale-[1.02]"
                  />
                  <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </div>
              </div>

              <!-- Content -->
              <div class="space-y-4">
                <div class="flex items-center gap-3 mb-4">
                  <div class="p-2 bg-purple-100 rounded-lg">
                    <FileText class="w-5 h-5 text-purple-600" />
                  </div>
                  <h3 class="text-xl font-semibold text-gray-900">Content</h3>
                </div>
                <div class="prose prose-lg max-w-none bg-white p-6 rounded-2xl shadow-lg border border-gray-100">
                  <div v-html="selectedAnnouncement.content" class="text-gray-700 leading-relaxed"></div>
                </div>
              </div>

              <!-- Attachments -->
              <div v-if="selectedAnnouncement.attachments && selectedAnnouncement.attachments.length > 0" class="space-y-4">
                <div class="flex items-center gap-3 mb-4">
                  <div class="p-2 bg-green-100 rounded-lg">
                    <Download class="w-5 h-5 text-green-600" />
                  </div>
                  <h3 class="text-xl font-semibold text-gray-900">Attachments</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div
                    v-for="attachment in selectedAnnouncement.attachments"
                    :key="attachment.path"
                    class="group p-4 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl border border-gray-200 hover:border-blue-300 transition-all duration-200 hover:shadow-lg"
                  >
                    <div class="flex items-center gap-3">
                      <div class="p-2 bg-white rounded-lg shadow-sm">
                        <FileText class="w-5 h-5 text-gray-600" />
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900 truncate">{{ attachment.name }}</p>
                        <p class="text-sm text-gray-500">{{ formatFileSize(attachment.size) }}</p>
                      </div>
                      <Button
                        variant="outline"
                        size="sm"
                        @click="downloadAttachment(attachment)"
                        class="opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                      >
                        <Download class="w-4 h-4 mr-1" />
                        Download
                      </Button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Metadata Cards -->
              <div class="space-y-4">
                <div class="flex items-center gap-3 mb-4">
                  <div class="p-2 bg-blue-100 rounded-lg">
                    <Info class="w-5 h-5 text-blue-600" />
                  </div>
                  <h3 class="text-xl font-semibold text-gray-900">Announcement Details</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                  <!-- Author Card -->
                  <div class="p-6 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl border border-blue-200">
                    <div class="flex items-center gap-3 mb-3">
                      <div class="p-2 bg-blue-100 rounded-lg">
                        <User class="w-5 h-5 text-blue-600" />
                      </div>
                      <h4 class="font-semibold text-blue-900">Author</h4>
                    </div>
                    <p class="text-blue-800 font-medium">
                      {{ selectedAnnouncement.author_name || 'Not specified' }}
                    </p>
                    <p v-if="selectedAnnouncement.author_position" class="text-blue-600 text-sm mt-1">
                      {{ selectedAnnouncement.author_position }}
                    </p>
                  </div>

                  <!-- Publication Card -->
                  <div class="p-6 bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl border border-green-200">
                    <div class="flex items-center gap-3 mb-3">
                      <div class="p-2 bg-green-100 rounded-lg">
                        <Calendar class="w-5 h-5 text-green-600" />
                      </div>
                      <h4 class="font-semibold text-green-900">Publication</h4>
                    </div>
                    <p class="text-green-800 font-medium">
                      {{ selectedAnnouncement.published_at ? formatDate(selectedAnnouncement.published_at) : 'Not published' }}
                    </p>
                    <p v-if="selectedAnnouncement.expires_at" class="text-green-600 text-sm mt-1">
                      Expires: {{ formatDate(selectedAnnouncement.expires_at) }}
                    </p>
                  </div>

                  <!-- Status Card -->
                  <div class="p-6 bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl border border-purple-200">
                    <div class="flex items-center gap-3 mb-3">
                      <div class="p-2 bg-purple-100 rounded-lg">
                        <CheckCircle class="w-5 h-5 text-purple-600" />
                      </div>
                      <h4 class="font-semibold text-purple-900">Status</h4>
                    </div>
                    <div class="flex flex-wrap gap-2">
                      <Badge :variant="selectedAnnouncement.is_published ? 'default' : 'secondary'" class="text-sm">
                        {{ selectedAnnouncement.is_published ? 'Published' : 'Draft' }}
                      </Badge>
                      <Badge v-if="selectedAnnouncement.is_featured" variant="secondary" class="bg-purple-100 text-purple-800 text-sm">
                        Featured
                      </Badge>
                    </div>
                  </div>

                  <!-- Priority Card -->
                  <div class="p-6 bg-gradient-to-br from-orange-50 to-red-50 rounded-xl border border-orange-200">
                    <div class="flex items-center gap-3 mb-3">
                      <div class="p-2 bg-orange-100 rounded-lg">
                        <AlertTriangle class="w-5 h-5 text-orange-600" />
                      </div>
                      <h4 class="font-semibold text-orange-900">Priority Level</h4>
                    </div>
                    <Badge
                      :variant="getPriorityVariant(selectedAnnouncement.priority)"
                      class="text-sm font-semibold"
                    >
                      {{ selectedAnnouncement.priority.toUpperCase() }}
                    </Badge>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Enhanced Action Bar -->
          <div class="border-t border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100 p-6">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="p-2 bg-white rounded-lg shadow-sm">
                  <Clock class="w-5 h-5 text-gray-600" />
                </div>
                <div>
                  <p class="text-sm text-gray-500">Last updated</p>
                  <p class="font-semibold text-gray-900">
                    {{ selectedAnnouncement.updated_at ? formatDate(selectedAnnouncement.updated_at) : 'Unknown' }}
                  </p>
                </div>
              </div>

              <div class="flex gap-3">
                <Button variant="outline" @click="viewSheetOpen = false" class="px-6">
                  <X class="w-4 h-4 mr-2" />
                  Close
                </Button>
                <Button @click="openEditSheet(selectedAnnouncement)" class="px-6 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700">
                  <Edit class="w-4 h-4 mr-2" />
                  Edit Announcement
                </Button>
              </div>
            </div>
          </div>
        </div>
      </SheetContent>
    </Sheet>

    <!-- Edit Sheet -->
    <Sheet :open="editSheetOpen" @update:open="editSheetOpen = $event">
      <SheetContent class="w-full sm:w-[800px] lg:w-[1000px] xl:w-[1200px]">
        <SheetHeader>
          <SheetTitle>Edit Announcement</SheetTitle>
          <SheetDescription>
            Update the announcement details.
          </SheetDescription>
        </SheetHeader>

        <form @submit.prevent="submitEdit" class="space-y-6 mt-6" v-if="selectedAnnouncement">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="sm:col-span-2">
              <Label for="edit-title">Title *</Label>
              <Input
                id="edit-title"
                v-model="editForm.title"
                placeholder="Enter announcement title"
                :class="{ 'border-red-500': editForm.errors.title }"
              />
              <p v-if="editForm.errors.title" class="text-red-500 text-sm mt-1">
                {{ editForm.errors.title }}
              </p>
            </div>

            <div>
              <Label for="edit-type">Type *</Label>
              <Select v-model="editForm.type">
                <SelectTrigger>
                  <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="general">General</SelectItem>
                  <SelectItem value="urgent">Urgent</SelectItem>
                  <SelectItem value="event">Event</SelectItem>
                  <SelectItem value="notice">Notice</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="editForm.errors.type" class="text-red-500 text-sm mt-1">
                {{ editForm.errors.type }}
              </p>
            </div>

            <div>
              <Label for="edit-priority">Priority *</Label>
              <Select v-model="editForm.priority">
                <SelectTrigger>
                  <SelectValue placeholder="Select priority" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="low">Low</SelectItem>
                  <SelectItem value="normal">Normal</SelectItem>
                  <SelectItem value="high">High</SelectItem>
                  <SelectItem value="urgent">Urgent</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="editForm.errors.priority" class="text-red-500 text-sm mt-1">
                {{ editForm.errors.priority }}
              </p>
            </div>

            <div>
              <Label for="edit-published_at">Published Date</Label>
              <Input
                id="edit-published_at"
                v-model="editForm.published_at"
                type="datetime-local"
              />
              <p v-if="editForm.errors.published_at" class="text-red-500 text-sm mt-1">
                {{ editForm.errors.published_at }}
              </p>
            </div>

            <div>
              <Label for="edit-expires_at">Expires Date</Label>
              <Input
                id="edit-expires_at"
                v-model="editForm.expires_at"
                type="datetime-local"
              />
              <p v-if="editForm.errors.expires_at" class="text-red-500 text-sm mt-1">
                {{ editForm.errors.expires_at }}
              </p>
            </div>

            <div>
              <Label for="edit-author_name">Author Name</Label>
              <Input
                id="edit-author_name"
                v-model="editForm.author_name"
                placeholder="Enter author name"
              />
            </div>

            <div>
              <Label for="edit-author_position">Author Position</Label>
              <Input
                id="edit-author_position"
                v-model="editForm.author_position"
                placeholder="Enter author position"
              />
            </div>
          </div>

          <div>
            <Label for="edit-content">Content *</Label>
            <Textarea
              id="edit-content"
              v-model="editForm.content"
              placeholder="Enter announcement content"
              rows="6"
              :class="{ 'border-red-500': editForm.errors.content }"
            />
            <p v-if="editForm.errors.content" class="text-red-500 text-sm mt-1">
              {{ editForm.errors.content }}
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
              <Label for="edit-image">Image</Label>
              <Input
                id="edit-image"
                type="file"
                accept="image/*"
                @change="handleEditImageUpload"
              />
              <p class="text-sm text-gray-500 mt-1">Max 2MB. Supported: JPEG, PNG, JPG, GIF, WebP</p>
              <div v-if="selectedAnnouncement.image_path" class="mt-2">
                <img
                  :src="`/storage/${selectedAnnouncement.image_path}`"
                  :alt="selectedAnnouncement.title"
                  class="w-32 h-20 object-cover rounded border"
                />
              </div>
            </div>

            <div>
              <Label for="edit-attachments">Attachments</Label>
              <Input
                id="edit-attachments"
                type="file"
                multiple
                @change="handleEditAttachmentsUpload"
              />
              <p class="text-sm text-gray-500 mt-1">Max 10MB each. Supported: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT</p>

              <!-- Existing Attachments -->
              <div v-if="selectedAnnouncement.attachments && selectedAnnouncement.attachments.length > 0" class="mt-3 space-y-2">
                <Label class="text-sm font-medium">Existing Attachments</Label>
                <div
                  v-for="attachment in selectedAnnouncement.attachments"
                  :key="attachment.path"
                  class="flex items-center justify-between p-2 bg-gray-50 rounded text-sm"
                >
                  <span>{{ attachment.name }}</span>
                  <Button
                    variant="outline"
                    size="sm"
                    @click="deleteAttachment(attachment)"
                    class="text-red-600 hover:text-red-700"
                  >
                    <Trash2 class="w-3 h-3" />
                  </Button>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-4">
            <div class="flex items-center space-x-2">
              <Switch
                id="edit-is_published"
                v-model:checked="editForm.is_published"
              />
              <Label for="edit-is_published">Published</Label>
            </div>

            <div class="flex items-center space-x-2">
              <Switch
                id="edit-is_featured"
                v-model:checked="editForm.is_featured"
              />
              <Label for="edit-is_featured">Featured</Label>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-6 border-t">
            <Button type="button" variant="outline" @click="editSheetOpen = false">
              Cancel
            </Button>
            <Button type="submit" :disabled="editForm.processing">
              <Loader2 v-if="editForm.processing" class="w-4 h-4 mr-2 animate-spin" />
              Update Announcement
            </Button>
          </div>
        </form>
      </SheetContent>
    </Sheet>

    <!-- Delete Dialog -->
    <Dialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete Announcement</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete "{{ selectedAnnouncement?.title }}"? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>

        <div class="flex justify-end gap-3 mt-6">
          <Button variant="outline" @click="deleteDialogOpen = false">
            Cancel
          </Button>
          <Button variant="destructive" @click="submitDelete" :disabled="deleteForm.processing">
            <Loader2 v-if="deleteForm.processing" class="w-4 h-4 mr-2 animate-spin" />
            Delete Announcement
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  </StaffLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import {
  Plus,
  Edit,
  Eye,
  EyeOff,
  Trash2,
  Star,
  FileText,
  CheckCircle,
  X,
  Loader2,
  Download,
  Bell,
  User,
  Calendar,
  Clock,
  Info,
  AlertTriangle,
} from 'lucide-vue-next';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import StaffLayout from '@/layouts/staff/Layout.vue';

// Props
const props = defineProps<{
  announcements: any[];
  statistics: any;
  filters: any;
}>();

// Toast is imported directly from vue-sonner

// Breadcrumbs
const { staffAnnouncementsBreadcrumbs } = useBreadcrumbs();

// State
const createSheetOpen = ref(false);
const viewSheetOpen = ref(false);
const editSheetOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedAnnouncement = ref<any>(null);
const filters = ref(props.filters || {});

// Forms
const createForm = useForm({
  title: '',
  content: '',
  type: 'general',
  priority: 'normal',
  is_published: false,
  is_featured: false,
  published_at: '',
  expires_at: '',
  author_name: '',
  author_position: '',
  image: null as File | null,
  attachments: [] as File[],
});

const editForm = useForm({
  title: '',
  content: '',
  type: 'general',
  priority: 'normal',
  is_published: false,
  is_featured: false,
  published_at: '',
  expires_at: '',
  author_name: '',
  author_position: '',
  image: null as File | null,
  attachments: [] as File[],
});

const deleteForm = useForm({});

// Methods
const openCreateSheet = () => {
  createForm.reset();
  createSheetOpen.value = true;
};

const openViewSheet = (announcement: any) => {
  selectedAnnouncement.value = announcement;
  viewSheetOpen.value = true;
};

const openEditSheet = (announcement: any) => {
  selectedAnnouncement.value = announcement;
  editForm.title = announcement.title;
  editForm.content = announcement.content;
  editForm.type = announcement.type;
  editForm.priority = announcement.priority;
  editForm.is_published = announcement.is_published;
  editForm.is_featured = announcement.is_featured;
  editForm.published_at = announcement.published_at ? announcement.published_at.split('T')[0] + 'T' + announcement.published_at.split('T')[1].substring(0, 5) : '';
  editForm.expires_at = announcement.expires_at ? announcement.expires_at.split('T')[0] + 'T' + announcement.expires_at.split('T')[1].substring(0, 5) : '';
  editForm.author_name = announcement.author_name || '';
  editForm.author_position = announcement.author_position || '';
  editForm.image = null;
  editForm.attachments = [];
  editSheetOpen.value = true;
};

const openDeleteDialog = (announcement: any) => {
  selectedAnnouncement.value = announcement;
  deleteDialogOpen.value = true;
};

const submitCreate = () => {
  createForm.post(route('staff.announcements.store'), {
    forceFormData: true,
    onSuccess: () => {
      createSheetOpen.value = false;
      toast.success('🎉 Announcement created successfully!', {
        description: 'Your announcement has been published and is now visible to the community.',
        duration: 5000,
      });
    },
    onError: (errors: any) => {
      toast.error('❌ Failed to create announcement', {
        description: 'Please check the form for errors and try again.',
        duration: 5000,
      });
    },
  });
};

const submitEdit = () => {
  editForm.put(route('staff.announcements.update', selectedAnnouncement.value.id), {
    forceFormData: true,
    onSuccess: () => {
      editSheetOpen.value = false;
      toast.success('✅ Announcement updated successfully!', {
        description: 'Your changes have been saved and are now live.',
        duration: 5000,
      });
    },
    onError: (errors: any) => {
      toast.error('❌ Failed to update announcement', {
        description: 'Please check the form for errors and try again.',
        duration: 5000,
      });
    },
  });
};

const submitDelete = () => {
  deleteForm.delete(route('staff.announcements.destroy', selectedAnnouncement.value.id), {
    onSuccess: () => {
      deleteDialogOpen.value = false;
      toast.success('🗑️ Announcement deleted successfully!', {
        description: 'The announcement has been permanently removed from the system.',
        duration: 5000,
      });
    },
    onError: (errors: any) => {
      toast.error('❌ Failed to delete announcement', {
        description: 'Please try again or contact support if the issue persists.',
        duration: 5000,
      });
    },
  });
};

const togglePublication = async (announcement: any) => {
  try {
    const response = await fetch(route('staff.announcements.toggle-publication', announcement.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    });

    const data = await response.json();

    if (data.success) {
      toast.success(data.message);
      // Refresh the page to update the data
      window.location.reload();
    } else {
      toast.error(data.message);
    }
  } catch (error) {
    toast.error('Failed to toggle publication status.');
  }
};

const toggleFeatured = async (announcement: any) => {
  try {
    const response = await fetch(route('staff.announcements.toggle-featured', announcement.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    });

    const data = await response.json();

    if (data.success) {
      toast.success(data.message);
      // Refresh the page to update the data
      window.location.reload();
    } else {
      toast.error(data.message);
    }
  } catch (error) {
    toast.error('Failed to toggle featured status.');
  }
};

const deleteAttachment = async (attachment: any) => {
  try {
    const response = await fetch(route('staff.announcements.delete-attachment', selectedAnnouncement.value.id), {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        attachment_path: attachment.path,
      }),
    });

    const data = await response.json();

    if (data.success) {
      toast.success(data.message);
      // Update the selected announcement
      selectedAnnouncement.value = data.announcement;
    } else {
      toast.error(data.message);
    }
  } catch (error) {
    toast.error('Failed to delete attachment.');
  }
};

const downloadAttachment = (attachment: any) => {
  window.open(`/storage/${attachment.path}`, '_blank');
};

const handleImageUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    createForm.image = target.files[0];
  }
};

const handleAttachmentsUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    createForm.attachments = Array.from(target.files);
  }
};

const handleEditImageUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    editForm.image = target.files[0];
  }
};

const handleEditAttachmentsUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    editForm.attachments = Array.from(target.files);
  }
};

const applyFilters = () => {
  // Apply filters by reloading the page with query parameters
  const queryParams = new URLSearchParams();
  Object.entries(filters.value).forEach(([key, value]) => {
    if (value && value !== 'all') {
      queryParams.append(key, value as string);
    }
  });

  const url = new URL(window.location.href);
  url.search = queryParams.toString();
  window.location.href = url.toString();
};

const clearFilters = () => {
  filters.value = {};
  applyFilters();
};

// Navigation functions
const goToCreate = () => {
  router.visit('/staff/announcements/create');
};

const goToEdit = (announcement: any) => {
  router.visit(`/staff/announcements/${announcement.id}/edit`);
};

const getPriorityVariant = (priority: string) => {
  switch (priority) {
    case 'urgent': return 'destructive';
    case 'high': return 'default';
    case 'normal': return 'secondary';
    case 'low': return 'outline';
    default: return 'secondary';
  }
};

const getTypeVariant = (type: string) => {
  switch (type) {
    case 'urgent': return 'destructive';
    case 'event': return 'default';
    case 'notice': return 'secondary';
    case 'general': return 'outline';
    default: return 'secondary';
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Set breadcrumbs
onMounted(() => {
  // Set breadcrumbs if needed
});
</script>
