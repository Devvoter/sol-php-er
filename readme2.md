

# Implementation Documentation for Task 2 of IPP 2024/2025

Name and surname: Denys Pylypenko\
Login: xpylypd00\

## Overall Design Philosophy
From the very beginning, I was thinking about one thing: in SOL25, absolutely everything is an object. So it made sense to build the interpreter in a way that respects that. I tried to keep things simple, modular, and easy to follow. If there's a method, class, or value — it should be an object. That idea guided how I wrote and split up the code.


## Architecture and Class Responsibilities
To keep the code organized, I split it into logical parts — each class has its own role and job:

- `Interpreter`: The main engine - it loads the program and keeps track of all known classes. It also knows how to run blocks, evaluate expressions, and create new objects.
- `ClassDefinition`: Wraps `<class>` elements from the XML AST. It stores the class name, optional parent, and a list of methods.
- `MethodDefinition`: Holds a method's selector (like `print` or `plus:`) and the block of code to run when it's called.
- `ExecutionContext`: Keeps track of local variables during method/block execution and also remembers what self refers to.
- `SolObject` and `Derived Classes`: The base class for all objects. It stores instance attributes and supports method calls. Other types extend from it:
	- `SolInteger`, `SolString` — to represent numbers and strings.
	- `SolBlock` — represents a block literal, with its parameters and captured `self`.
	- `SolNil`, `SolTrue`, `SolFalse` — represent special single instances.
	- `SolUserObject` — used for user-defined classes.

Class relationships and dependencies:

- Interpreter keeps all class definitions.
- Classes know their methods and parent.
- Contexts hold the variables during execution.

#### UML Diagram
[Click](umlDiagram/umlClasses-v2.png)

## Core Interpreter Methods

- `interpretBlock()` – run a `<block>` node

	- Collects all `<assign>` elements, sorts them by order, and executes them one‑by‑one.
	- Tracks the value of the last assignment and returns it; if the block is empty, returns the singleton nil object.
	- Parameters are already bound by the caller (`MethodDefinition->execute` or `SolBlock->callMethod`), so the method only touches local assignments.

- `interpretAssign()` – handle `<assign>`

	- Extracts the target variable name from `<var>`.
	- Evaluates the nested `<expr>` via `evaluateExpr()`.
	- Stores the result in `ExecutionContext`. The context throws if the code tries to overwrite `self`, `super`, or a formal parameter, so we keep the method itself lean.

- `evaluateExpr()` – evaluate `<expr>`

	Handles every expression shape that can appear in SOL25:

	- **Literal** – builds the matching object:

		- `Integer` → `SolInteger`, using `intval`.
		- `String`  → `SolString`, with `stripcslashes` to decode escapes (` `, `\'` …).
		- `True/False/Nil` → returns the shared singleton instances.
	- **Variable** – resolves `self` from the context or looks up a local variable; global keywords `nil/true/false` are handled like literals. (Direct super dispatch is still in progress — see Limitations.)
	- **Block literal** – creates a `SolBlock`, capturing the current `self` for static scope.

	- **Message send** – the largest branch.
		- Supports class messages like `String read`, `ClassName new`, and `ClassName from:` by recognising a receiver literal of class and calling `createInstance()` when needed.
		- Collects `<arg>` elements into an ordered list before dispatch.
		- Delegates regular instance messages to `SolObject->callMethod()`, which already implements method lookup, attribute getters/setters, and built‑ins.

This trio (`interpretBlock`, `interpretAssign`, `evaluateExpr`) make up the heartbeat of the interpreter, translating the spec almost line‑for‑line into code — the few deviations are purely practical (for instance, a tiny fast‑path for `String read`).

## Design Patterns
While I was thinking and implementing interpreter, I had an idea to apply the **Factory Method** design pattern. This decision arose from the need to simplify the logic around creating different types of PHP objects depending on the SOL25 class names. For example, an XML element `<literal class="Integer">` results in creating a `SolInteger` object, while`<literal class="String">` leads to a `SolString` object. User-defined classes from the AST create `SolUserObject` instances linked to `ClassDefinition`. This method is encapsulated within the Interpreter class in the form of the `createInstance($className): SolObject method`. It uses a simple conditional structure (like `switch` or `if-else`) to determine which object type to instantiate, effectively reducing code duplication and simplifying future extensions for additional built-in classes.

#### Mapping to Factory Method Pattern
- **Client code**: parts of the interpreter that need a new `SolObject`.
- **Creator**: `IPP\Student\Interpreter\`
- **Factory method**: `public function createInstance(string $className, ?int $init = null): SolObject {...}`.
- **Concrete Creators**: the `switch` or `if` branches inside `createInstance()`.
- **Product**: abstract `SolObject`.
- **Concrete Products**: `SolInteger`, `SolString`, `SolNil`, `SolTrue`, `SolFalse`, `SolUserObject`, etc.

## Specific Implementation Choices and IPP‑core Usage
- **XML reading** — I used PHP’s `DOMDocument`, no extra libraries, just native tools.
- Leveraged IPP‑core exceptions (`ParseMainException`, `InterpretValueException`, `InterpretDnuException`) to map SOL25 error codes (31, 32, 51, 53) directly to thrown PHP exceptions.
- **Method arguments** — Ordered by the `order` attribute using `ksort()` to make sure they're passed in the right sequence.
- **Block execution** — When creating a `SolBlock`, it captures the current `self`, so it works even if called later somewhere else.

## Limitations and Unfinished Features (if any)
- **super** calls don’t always work — resolving inherited built-in methods is tricky.
- Inheritance of instance attributes sometimes fails, especially with `Integer` subclasses.
- **Unimplemented `Block new`** Calling `new` on `Block` is not yet supported (throws `NotImplementedException`)
- Some optional SOL25 built‑in messages (e.g. `timesRepeat:` for zero‑arity blocks) are partially implemented or untested.
- Complex assignments might be evaluated in the wrong order (depends on `order` attributes).



