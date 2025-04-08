<!DOCTYPE html>
<html>
<head>
    <title>Todo App</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            overflow-x: hidden;
            background-color: #f1f3f5;
        }

        .app-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
            max-width: 100vw;
        }

        .sidebar {
            width: 250px;
            background-color: #fff;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            padding: 20px;
            flex-shrink: 0;
        }

        .sidebar .user-icon {
            font-size: 60px;
            margin-bottom: 20px;
            color: #6c757d;
        }

        .sidebar .btn {
            width: 100%;
            margin-bottom: 15px;
        }

        .main-content {
            flex: 1;
            padding: 40px 20px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .completed {
            text-decoration: line-through;
            color: gray;
        }

        .task-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease-in-out;
        }

        .task-item:hover {
            background-color: #f1f3f5;
        }

        .task-item span {
            word-break: break-word;
            max-width: 100%;
            display: inline-block;
        }

        #taskForm input[type="text"] {
            min-width: 0;
            flex: 1;
        }

        @media (max-width: 768px) {
            .app-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                box-shadow: none;
            }
        }

        .main-div {
            width: 40%;
            padding: 10px 20px;
            margin-left: 20%;
        }

        #newTaskWrapper {
            max-height: 350px;
            overflow-y: auto;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* Internet Explorer 10+ */
        }

        #newTaskWrapper::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
    </style>
</head>
<body>

    <div class="app-container">
        <div class="sidebar">
            <div class="text-center">
                <i class="bi bi-person-circle user-icon"></i>
            </div>
            <button class="btn btn-outline-secondary" onclick="showAll()">Show All Tasks</button>
            <button class="btn btn-outline-danger" onclick="hideCompleted()">Hide All Tasks</button>
        </div>
        <div class="main-div">
            <div class="main-content">
                <h2 class="mb-4 text-center">📝 To-Do List</h2>

                <form id="taskForm" class="mb-4 d-flex gap-2">
                    <input type="text" id="task" class="form-control" placeholder="Enter a task">
                    <button type="submit" class="btn btn-primary text-nowrap">Add Task</button>
                </form>

                <!-- Scrollable wrapper for new tasks -->
                <div id="newTaskWrapper">
                    <ul class="list-unstyled" id="newTaskList">
                        @foreach($tasks as $task)
                        <li class="task-item {{ $task->is_completed ? 'completed' : '' }}" data-id="{{ $task->id }}">
                            <div>
                                <input type="checkbox" class="form-check-input me-2" onchange="toggleTask({{ $task->id }})" {{ $task->is_completed ? 'checked' : '' }}>
                                <span>{{ $task->title }}</span>
                            </div>
                            <button class="btn btn-danger btn-sm" onclick="deleteTask({{ $task->id }})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>

                <h4 id="allTasksHeading" class="mb-3" style="display: none;">📋 All Tasks</h4>
                <ul class="list-unstyled" id="allTaskList" style="display: none;"></ul>
            </div>
        </div>
    </div>

    <script>
        const csrf = $('meta[name="csrf-token"]').attr('content');

        $('#taskForm').on('submit', function (e) {
            e.preventDefault();
            addTask();
        });

        function addTask() {
            const title = $('#task').val().trim();
            if (!title) return alert("Task cannot be empty.");

            $.ajax({
                url: "{{ route('store') }}",
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                data: { title: title },
                success: function (task) {
                    const li = `
                        <li class="task-item" data-id="${task.id}">
                            <div>
                                <input type="checkbox" class="form-check-input me-2" onchange="toggleTask(${task.id})">
                                <span>${task.title}</span>
                            </div>
                            <button class="btn btn-danger btn-sm" onclick="deleteTask(${task.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </li>
                    `;
                    $('#newTaskList').prepend(li);
                    $('#task').val('');
                    $('#allTaskList').hide();
                    $('#allTasksHeading').hide();
                    $('#newTaskWrapper').show();
                },
                error: function () {
                    alert("Duplicate task.");
                }
            });
        }

        function toggleTask(id) {
            $.ajax({
                url: `/tasks/${id}/toggle`,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf },
                success: function () {
                    $(`li[data-id="${id}"]`).fadeOut(300, function () {
                        $(this).remove();
                    });
                }
            });
        }

        function deleteTask(id) {
            if (!confirm("Are you sure to delete this task?")) return;

            $.ajax({
                url: `/tasks/${id}`,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf },
                success: function () {
                    $(`li[data-id="${id}"]`).fadeOut(300, function () {
                        $(this).remove();
                    });
                }
            });
        }

        function showAll() {
            $.ajax({
                url: '/tasks/all',
                type: 'GET',
                success: function (tasks) {
                    $('#newTaskWrapper').hide();
                    $('#allTaskList').empty().show();
                    $('#allTasksHeading').show();

                    tasks.forEach(task => {
                        const li = `
                            <li class="task-item ${task.is_completed ? 'completed' : ''}" data-id="${task.id}">
                                <div>
                                    <input type="checkbox" class="form-check-input me-2" onchange="toggleTask(${task.id})" ${task.is_completed ? 'checked' : ''}>
                                    <span>${task.title}</span>
                                </div>
                                <button class="btn btn-danger btn-sm" onclick="deleteTask(${task.id})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </li>
                        `;
                        $('#allTaskList').append(li);
                    });
                }
            });
        }

        function hideCompleted() {
            $('#newTaskWrapper').fadeOut();
            $('#allTaskList').fadeOut();
            $('#allTasksHeading').fadeOut();
        }
    </script>

</body>
</html>
